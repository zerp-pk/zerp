<?php

namespace App\Http\Controllers;

use App\Events\CreateHelpdeskReply;
use App\Events\DestroyHelpdeskReply;
use App\Http\Requests\StoreHelpdeskReplyRequest;
use App\Models\HelpdeskReply;
use App\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Auth;

class HelpdeskReplyController extends Controller
{
    public function store(StoreHelpdeskReplyRequest $request, HelpdeskTicket $ticket)
    {
        if(Auth::user()->can('create-helpdesk-replies')){
            $validated = $request->validated();

            $cleanMessage = trim(str_replace('&nbsp;', '', strip_tags($validated['message'])));
            $hasAttachments = !empty($validated['attachments']);

            if(empty($cleanMessage) && !$hasAttachments){
                return response()->json([
                    'success' => false,
                    'message' => __('Reply message cannot be empty.')
                ], 422);
            }

            $reply = new HelpdeskReply();
            $reply->ticket_id = $ticket->id;
            $reply->message = $validated['message'];

            // Handle multiple attachments
            if (isset($validated['attachments']) && $validated['attachments']) {
                $attachmentPaths = is_array($validated['attachments']) ? $validated['attachments'] : [$validated['attachments']];
                $filenames = array_map('basename', array_filter($attachmentPaths));
                $reply->attachments = !empty($filenames) ? json_encode($filenames) : null;
            }

            $reply->is_internal = $request->boolean('is_internal', false);
            $reply->created_by = Auth::id();
            $reply->save();

            CreateHelpdeskReply::dispatch($request, $reply);

            // Ensure attachments is always an array for frontend
            $replyData = $reply->load('creator')->toArray();
            if (isset($replyData['attachments']) && is_string($replyData['attachments'])) {
                $replyData['attachments'] = json_decode($replyData['attachments'], true) ?: [];
            }

            return response()->json([
                'success' => true,
                'message' => __('Reply added successfully'),
                'reply' => $replyData
            ]);
        }
        else{
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function destroy($id)
    {
        if(Auth::user()->can('delete-helpdesk-replies')){
            $helpdeskReply = HelpdeskReply::find($id);
            DestroyHelpdeskReply::dispatch($helpdeskReply);
            $helpdeskReply->delete();

            session()->flash('success', __('Reply deleted successfully'));

            return response()->json([
                'success' => true,
                'message' => __('Reply deleted successfully')
            ]);
        }
        else{
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }
}
