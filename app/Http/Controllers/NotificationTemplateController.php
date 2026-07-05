<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationTemplateLang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-notification-templates'))
        {
            $allTypes = Notification::where('type', '!=', 'mail')->distinct()->orderBy('type')->pluck('type')->filter()->values()->toArray();
            $activeType = $request->get('type', $allTypes[0] ?? '');

            $query = Notification::where('type', $activeType);

            if ($request->filled('action')) {
                $query->where('action', 'like', '%' . $request->action . '%');
            }

            $sortField = $request->get('sort') ?: 'id';
            $query->orderBy($sortField, $request->get('direction', 'asc'));

            $notificationTemplates = $query->paginate($request->get('per_page', 10))->withQueryString();

            return Inertia::render('notification-templates/Index', [
                'notificationTemplates' => $notificationTemplates,
                'allTypes' => $allTypes,
                'activeType' => $activeType,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function edit(Notification $notificationTemplate, Request $request)
    {
        if(Auth::user()->can('edit-notification-templates'))
        {
            $lang = $request->get('lang', 'en');
            $currNotificationTempLang = $this->findLanguageContent($notificationTemplate->id, $lang);
            $templateLangs = NotificationTemplateLang::where('parent_id', $notificationTemplate->id)->get();
            $variables = json_decode($currNotificationTempLang->variables ?? '{}', true);

            return Inertia::render('notification-templates/Edit', [
                'notificationTemplate' => $notificationTemplate,
                'templateLangs' => $templateLangs,
                'currNotificationTempLang' => $currNotificationTempLang,
                'variables' => $variables,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function getLanguageContent(Notification $notificationTemplate, $lang)
    {
        if(Auth::user()->can('manage-notification-templates')){

            $langContent = $this->findLanguageContent($notificationTemplate->id, $lang);

            return response()->json([
                'subject' => $notificationTemplate->action,
                'content' => $langContent->content ?? '',
                'lang' => $lang
            ]);
        }
        return response()->json(['error' => 'Permission denied'], 403);
    }

    private function findLanguageContent($templateId, $lang)
    {
        $langContent = NotificationTemplateLang::where('parent_id', $templateId)
            ->where('lang', $lang)
            ->first();

        if (!$langContent) {
            $langContent = NotificationTemplateLang::where('parent_id', $templateId)
                ->where('lang', 'en')
                ->first();

            if ($langContent) {
                $langContent = $langContent->replicate();
                $langContent->lang = $lang;
                $langContent->content = '';
            } else {
                // Create a new empty template if none exists
                $langContent = new NotificationTemplateLang([
                    'parent_id' => $templateId,
                    'lang' => $lang,
                    'content' => ''
                ]);
            }
        }

        return $langContent;
    }

    public function update(Request $request, Notification $notificationTemplate)
    {
        if(Auth::user()->can('edit-notification-templates')){

            $request->validate([
                'content' => 'required|string',
                'lang' => 'required|string',
            ], [
                'content.required' => __('Notification content is required.'),
                'content.string' => __('Notification content must be a valid string.'),
                'lang.required' => __('Language is required.'),
                'lang.string' => __('Language must be a valid string.'),
            ]);

            $variables = NotificationTemplateLang::where('parent_id', $notificationTemplate->id)
                ->where('lang', 'en')
                ->value('variables');

            NotificationTemplateLang::updateOrCreate(
                [
                    'parent_id' => $notificationTemplate->id,
                    'lang' => $request->lang,
                ],
                [
                    'content' => $request->content,
                    'variables' => $variables,
                ]
            );
            return back()->with('success', __('The notification template details are updated successfully'));
        }
        return back()->with('error', __('Permission denied'));
    }
}
