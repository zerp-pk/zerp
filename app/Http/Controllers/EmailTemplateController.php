<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-email-templates')){
            $emailTemplates = EmailTemplate::query()
                ->with('templateLangs')
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('module_name'), fn($q) => $q->where('module_name', request('module_name')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $allModules = EmailTemplate::distinct()->pluck('module_name')->filter()->sort()->values();

            return Inertia::render('EmailTemplates/Index', [
                'emailTemplates' => $emailTemplates,
                'allModules' => $allModules,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function edit(EmailTemplate $emailTemplate, Request $request)
    {
        if (Auth::user()->can('edit-email-templates')) {

            $lang = $request->get('lang', 'en');
            $currEmailTempLang = $this->findLanguageContent($emailTemplate->id, $lang);
            $templateLangs = EmailTemplateLang::where('parent_id', $emailTemplate->id)->get();
            $variables = json_decode($currEmailTempLang->variables ?? '{}', true);

            return Inertia::render('EmailTemplates/Edit', [
                'emailTemplate' => $emailTemplate,
                'templateLangs' => $templateLangs,
                'currEmailTempLang' => $currEmailTempLang,
                'variables' => $variables,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function getLanguageContent(EmailTemplate $emailTemplate, $lang)
    {
        if (Auth::user()->can('edit-email-templates')) {
            $langContent = $this->findLanguageContent($emailTemplate->id, $lang);

            return response()->json([
                'subject' => $langContent->subject ?? '',
                'content' => $langContent->content ?? '',
                'lang' => $lang
            ]);
        }
        return response()->json(['error' => 'Permission denied'], 403);
    }

    private function findLanguageContent($templateId, $lang)
    {
        $langContent = EmailTemplateLang::where('parent_id', $templateId)
            ->where('lang', $lang)
            ->first();

        if (!$langContent) {
            $langContent = EmailTemplateLang::where('parent_id', $templateId)
                ->where('lang', 'en')
                ->first();

            if ($langContent) {
                $langContent = $langContent->replicate();
                $langContent->lang = $lang;
                $langContent->subject = '';
                $langContent->content = '';
            }
        }

        return $langContent;
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        if (Auth::user()->can('edit-email-templates')) {

            $request->validate([
                'subject' => 'required|string',
                'content' => 'required|string',
                'lang' => 'required|string',
            ], [
                'subject.required' => __('Email subject is required.'),
                'subject.string' => __('Email subject must be a valid string.'),
                'content.required' => __('Email content is required.'),
                'content.string' => __('Email content must be a valid string.'),
                'lang.required' => __('Language is required.'),
                'lang.string' => __('Language must be a valid string.'),
            ]);

            $variables = EmailTemplateLang::where('parent_id', $emailTemplate->id)
                ->where('lang', 'en')
                ->value('variables');

            EmailTemplateLang::updateOrCreate(
                [
                    'parent_id' => $emailTemplate->id,
                    'lang' => $request->lang,
                ],
                [
                    'subject' => $request->subject,
                    'content' => $request->content,
                    'variables' => $variables,
                ]
            );

            return redirect()->back()->with('success', __('The email template details are updated successfully.'));
        }

        return redirect()->route('email-templates.index')->with('error', __('Permission denied'));
    }

    public function updateMeta(Request $request, EmailTemplate $emailTemplate)
    {
        if (Auth::user()->can('edit-email-templates')) {

            $request->validate([
                'from' => 'required|string|max:255',
            ], [
                'from.required' => __('From address is required.'),
                'from.string' => __('From address must be a valid string.'),
                'from.max' => __('From address must not exceed 255 characters.'),
            ]);

            $emailTemplate->from = $request->from;
            $emailTemplate->save();

            return redirect()->back()->with('success', __('The email template details are updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied'));
    }
}
