<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\ChMessage as Message;
use App\Models\ChFavorite as Favorite;
use App\Models\ChPinned as Pinned;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Events\MessageSent;
use App\Events\UserOnline;
use App\Events\UserOffline;

class MessengerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->can('manage-messenger')) {
            // Get all users except current user with their last message and online status
            $userQuery = User::where('id', '!=', $user->id);
            // Filter by user permissions
                if ($user->type === 'superadmin') {
                    $userQuery->where('creator_id', creatorId());
                } elseif ($user->can('manage-any-users')) {
                    // Company or user with manage-any-users can see own + team users
                    $userQuery->where('created_by', creatorId());
                } elseif ($user->can('manage-own-users')) {
                    // Company or user with manage-own-users can see own users
                    $userQuery->where('creator_id', $user->id);
                } else {
                    // Default: only own users
                    $userQuery->where('id', creatorId());
                }

            
            $users = $userQuery->select('id', 'name', 'email', 'avatar', 'active_status')
                ->get()
                ->map(function ($chatUser) use ($user) {
                    $lastMessage = Message::where(function ($query) use ($user, $chatUser) {
                        $query->where('from_id', $user->id)->where('to_id', $chatUser->id);
                    })->orWhere(function ($query) use ($user, $chatUser) {
                        $query->where('from_id', $chatUser->id)->where('to_id', $user->id);
                    })->latest()->first();
                    
                    $unreadCount = Message::where('from_id', $chatUser->id)
                        ->where('to_id', $user->id)
                        ->where('seen', 0)
                        ->count();
                    
                    // Check if user is online using cache
                    $cacheKey = "user_online_{$chatUser->id}";
                    $isOnline = Cache::has($cacheKey);
                    
                    return [
                        'id' => $chatUser->id,
                        'name' => $chatUser->name,
                        'email' => $chatUser->email,
                        'avatar' => $chatUser->avatar,
                        'last_message' => $lastMessage,
                        'unread_count' => $unreadCount,
                        'is_online' => $isOnline,
                        'last_seen_at' => $chatUser->last_seen_at,
                    ];
                });

            // Get messages for selected user
            $messages = [];
            $selectedUserId = $request->get('user_id');
            
            if ($selectedUserId) {
                $selectedUser = User::find($selectedUserId);
                if ($selectedUser) {
                    // Mark messages as read
                    Message::where('from_id', $selectedUserId)
                        ->where('to_id', $user->id)
                        ->where('seen', 0)
                        ->update(['seen' => 1]);
                    
                    // Get conversation messages
                    $messages = Message::where(function ($query) use ($user, $selectedUserId) {
                        $query->where('from_id', $user->id)->where('to_id', $selectedUserId);
                    })->orWhere(function ($query) use ($user, $selectedUserId) {
                        $query->where('from_id', $selectedUserId)->where('to_id', $user->id);
                    })->where(function ($query) use ($user) {
                        // Filter out messages deleted by current user
                        $query->where(function ($q) use ($user) {
                            $q->where('from_id', $user->id)->where('deleted_by_sender', false);
                        })->orWhere(function ($q) use ($user) {
                            $q->where('to_id', $user->id)->where('deleted_by_receiver', false);
                        });
                    })->with(['fromUser', 'toUser'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'sender_id' => $message->from_id,
                            'receiver_id' => $message->to_id,
                            'message' => $message->body,
                            'is_read' => $message->seen,
                            'created_at' => $message->created_at->toISOString(),
                            'updated_at' => $message->updated_at->toISOString(),
                            'sender' => [
                                'id' => $message->fromUser->id,
                                'name' => $message->fromUser->name,
                                'email' => $message->fromUser->email,
                                'avatar' => $message->fromUser->avatar,
                            ],
                            'receiver' => [
                                'id' => $message->toUser->id,
                                'name' => $message->toUser->name,
                                'email' => $message->toUser->email,
                                'avatar' => $message->toUser->avatar,
                            ],
                        ];
                    });
                }
            }

            return Inertia::render('messenger/index', [
                'users' => $users,
                'messages' => $messages,
                'selectedUserId' => $selectedUserId ? (int) $selectedUserId : null,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function send(Request $request)
    {
        if (Auth::user()->can('send-messages')) {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'message' => 'nullable|string|max:1000',
                'attachment' => 'nullable|file',
            ], [
                'receiver_id.required' => __('Please select a user to send message to.'),
                'receiver_id.exists' => __('Selected user does not exist.'),
                'message.max' => __('Message cannot exceed 1000 characters.'),
            ]);

            $user = Auth::user();
            $receiverId = $request->receiver_id;
            $messageText = $request->message;
            $attachmentPath = null;

            // Handle file attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filenameWithExt = $file->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'attachment', $fileNameToStore, 'messenger');
                if ($upload['flag'] == 1) {
                    $attachmentPath = $upload['url'];
                } else {
                    return back()->with('error', $upload['msg']);
                }
            }

            // Validate that either message or attachment is provided
            if (!$messageText && !$attachmentPath) {
                return back()->with('error', __('Please provide a message or attachment.'));
            }

            // Create new message
            $message = Message::create([
                'from_id' => $user->id,
                'to_id' => $receiverId,
                'body' => $messageText ?: '',
                'attachment' => $attachmentPath,
                'seen' => 0,
            ]);

            if ($attachmentPath) {
                $media = \App\Services\MediaAttachmentService::resolveOrBackfill(
                    $attachmentPath,
                    \App\Models\ChMessage::class,
                    $message->id,
                    'chat_attachments',
                    $user->id,
                    $user->id,
                    \App\Services\MediaAttachmentService::ensureDirectory('Chat Attachments', $user->id, $user->id)
                );
                if ($media) {
                    $message->update(['media_id' => $media->id]);
                }
            }

            // Load relationships for broadcasting
            $message->load(['fromUser', 'toUser']);

            // Dispatch event for real-time updates
            event(new MessageSent($message, $receiverId));

            return response()->json(['success' => true, 'message' => __('Message sent successfully!')]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getContacts()
    {
        $user = Auth::user();
        
        // Apply same permission filtering as index method
        $userQuery = User::where('id', '!=', $user->id);
        
        if ($user->type === 'superadmin') {
            $userQuery->where('creator_id', creatorId());
        } elseif ($user->can('manage-any-users')) {
            $userQuery->where('created_by', creatorId());
        } elseif ($user->can('manage-own-users')) {
            $userQuery->where('creator_id', $user->id);
        } else {
            $userQuery->where('id', creatorId());
        }
        
        $users = $userQuery->select('id', 'name', 'email', 'avatar', 'last_seen_at')
            ->get()
            ->map(function ($chatUser) {
                $cacheKey = "user_online_{$chatUser->id}";
                $isOnline = Cache::has($cacheKey);
                
                return [
                    'id' => $chatUser->id,
                    'name' => $chatUser->name,
                    'email' => $chatUser->email,
                    'avatar' => $chatUser->avatar,
                    'is_online' => $isOnline,
                    'last_seen_at' => $chatUser->last_seen_at,
                ];
            });
            
        return response()->json($users);
    }

    public function getMessages($userId)
    {
        $user = Auth::user();
        $perPage = perPage(20);
        $page = request('page', 1);
        
        $messages = Message::where(function ($query) use ($user, $userId) {
            $query->where('from_id', $user->id)->where('to_id', $userId);
        })->orWhere(function ($query) use ($user, $userId) {
            $query->where('from_id', $userId)->where('to_id', $user->id);
        })->where(function ($query) use ($user) {
            // Filter out messages deleted by current user
            $query->where(function ($q) use ($user) {
                $q->where('from_id', $user->id)->where('deleted_by_sender', false);
            })->orWhere(function ($q) use ($user) {
                $q->where('to_id', $user->id)->where('deleted_by_receiver', false);
            });
        })->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);
        
        // Mark messages as read only on first page
        if ($page == 1) {
            Message::where('from_id', $userId)
                ->where('to_id', $user->id)
                ->where('seen', 0)
                ->update(['seen' => 1]);
        }
        
        $formattedMessages = $messages->getCollection()->map(function ($message) {
            $fromUser = User::find($message->from_id);
            $toUser = User::find($message->to_id);
            
            return [
                'id' => $message->id,
                'from_id' => $message->from_id,
                'to_id' => $message->to_id,
                'body' => $message->body,
                'attachment' => $message->attachment,
                'seen' => $message->seen,
                'created_at' => $message->created_at->toISOString(),
                'updated_at' => $message->updated_at->toISOString(),
                'from_user' => [
                    'id' => $fromUser->id,
                    'name' => $fromUser->name,
                    'email' => $fromUser->email,
                    'avatar' => $fromUser->avatar,
                ],
                'to_user' => [
                    'id' => $toUser->id,
                    'name' => $toUser->name,
                    'email' => $toUser->email,
                    'avatar' => $toUser->avatar,
                ],
            ];
        })->reverse()->values(); // Convert to chronological order (oldest first) for display
        
        return response()->json([
            'data' => $formattedMessages,
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
            'next_page_url' => $messages->nextPageUrl(),
            'prev_page_url' => $messages->previousPageUrl(),
        ]);
    }

    public function toggleFavorite(Request $request)
    {
        if (Auth::user()->can('toggle-favorite-messages')) {        
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ], [
                'user_id.required' => __('User ID is required.'),
                'user_id.exists' => __('Selected user does not exist.'),
            ]);

            $user = Auth::user();
            $favoriteUserId = $request->user_id;

            $favorite = Favorite::where('user_id', $user->id)
                ->where('favorite_id', $favoriteUserId)
                ->first();

            if ($favorite) {
                $favorite->delete();
                $isFavorite = false;
            } else {
                Favorite::create([
                    'user_id' => $user->id,
                    'favorite_id' => $favoriteUserId,
                ]);
                $isFavorite = true;
            }

            return response()->json(['is_favorite' => $isFavorite]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getFavorites()
    {
        $user = Auth::user();
        
        $favoriteIds = Favorite::where('user_id', $user->id)
            ->pluck('favorite_id')
            ->toArray();
            
        return response()->json($favoriteIds);
    }

    public function editMessage(Request $request, $messageId)
    {
        if (Auth::user()->can('edit-messages')) {        
            $request->validate([
                'message' => 'required|string|max:1000',
            ], [
                'message.required' => __('Message cannot be empty.'),
                'message.max' => __('Message cannot exceed 1000 characters.'),
            ]);

            $user = Auth::user();
            $message = Message::where('id', $messageId)
                ->where('from_id', $user->id)
                ->first();

            if (!$message) {
                return response()->json(['error' => __('Message not found')], 404);
            }

            $message->update(['body' => $request->message]);

            return response()->json(['success' => true, 'message' => __('Message updated successfully.')]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteMessage($messageId)
    {
        $user = Auth::user();
        if ($user->can('delete-messages')) {  
            $message = Message::find($messageId);

            if (!$message || ($message->from_id !== $user->id && $message->to_id !== $user->id)) {
                return response()->json(['error' => __('Message not found')], 404);
            }

            // Mark as deleted by current user
            if ($message->from_id === $user->id) {
                $message->deleted_by_sender = true;
            } else {
                $message->deleted_by_receiver = true;
            }

            // If both users deleted, hard delete
            if ($message->deleted_by_sender && $message->deleted_by_receiver) {
                if ($message->media_id && $message->media) {
                    \App\Services\MediaAttachmentService::deleteMedia($message->media);
                }
                $message->delete();
            } else {
                $message->save();
            }

            return response()->json(['success' => true]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updatePresence(Request $request)
    {
        $user = Auth::user();
        $wasOnline = Cache::has("user_online_{$user->id}");
        Cache::put("user_online_{$user->id}", true, 300);
        
        // Broadcast online status if user wasn't online before
        if (!$wasOnline) {
            broadcast(new UserOnline($user->id));
        }
        
        return response()->json(['status' => 'online']);
    }

    public function setOffline(Request $request)
    {
        $user = Auth::user();
        $wasOnline = Cache::has("user_online_{$user->id}");
        Cache::forget("user_online_{$user->id}");
        
        // Broadcast offline status if user was online
        if ($wasOnline) {
            broadcast(new UserOffline($user->id));
        }
        
        return response()->json(['status' => 'offline']);
    }

    public function getOnlineUsers()
    {
        $user = Auth::user();
        $userQuery = User::where('id', '!=', $user->id);
        
        if ($user->type === 'superadmin') {
            $userQuery->where('creator_id', creatorId());
        } elseif ($user->can('manage-any-users')) {
            $userQuery->where('created_by', creatorId());
        } elseif ($user->can('manage-own-users')) {
            $userQuery->where('creator_id', $user->id);
        } else {
            $userQuery->where('id', creatorId());
        }
        
        $users = $userQuery->get(['id', 'name'])->map(function ($chatUser) {
            return [
                'id' => $chatUser->id,
                'name' => $chatUser->name,
                'is_online' => Cache::has("user_online_{$chatUser->id}")
            ];
        });
        
        return response()->json($users);
    }

    public function togglePin(Request $request)
    {
        if (Auth::user()->can('toggle-pinned-messages')) { 
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ], [
                'user_id.required' => __('User ID is required.'),
                'user_id.exists' => __('Selected user does not exist.'),
            ]);

            $user = Auth::user();
            $pinnedUserId = $request->user_id;

            $pinned = Pinned::where('user_id', $user->id)
                ->where('pinned_id', $pinnedUserId)
                ->first();

            if ($pinned) {
                $pinned->delete();
                $isPinned = false;
            } else {
                $pinnedCount = Pinned::where('user_id', $user->id)->count();
                if ($pinnedCount >= 3) {
                    return response()->json(['error' => __('You can only pin up to 3 chats')], 400);
                }
                Pinned::create([
                    'user_id' => $user->id,
                    'pinned_id' => $pinnedUserId,
                ]);
                $isPinned = true;
            }

            return response()->json(['is_pinned' => $isPinned]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getPinned()
    {
        $user = Auth::user();
        $pinnedIds = Pinned::where('user_id', $user->id)
            ->pluck('pinned_id')
            ->toArray();
        return response()->json($pinnedIds);
    }

    public function checkNewMessages()
    {
        $lastCheck = request('last_check', now()->subMinutes(1));
        
        $newMessages = Message::where('to_id', Auth::id())
            ->where('created_at', '>', $lastCheck)
            ->with(['fromUser:id,name,email,avatar'])
            ->get();

        $hasNewMessages = $newMessages->count() > 0;
        
        return response()->json([
            'has_new_messages' => $hasNewMessages,
            'timestamp' => now()->toISOString(),
            'new_messages_count' => $newMessages->count()
        ]);
    }
}