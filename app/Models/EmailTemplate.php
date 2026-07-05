<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\EmailTemplateLang;
use App\Mail\CommonEmailTemplate;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'from',
        'module_name',
        'creator_id',
        'created_by',
    ];

    public function template()
    {
        return $this->hasOne('App\Models\UserEmailTemplate'::class, 'template_id', 'id')->where('user_id', '=', Auth::user()->id);
    }

    public function templateLangs()
    {
        return $this->hasMany(EmailTemplateLang::class, 'parent_id');
    }

    public static function sendEmailTemplate($emailTemplate, $mailTo, $obj, $user_id = null, $creator_id = null)
    {
        if (!empty($user_id)) {
            $usr = User::where('id', $user_id)->first();
        } else {
            $usr = Auth::user();
        }

        //Remove Current Login user Email don't send mail to them

        $mailTo = array_values($mailTo);

        // find template is exist or not in our record
        $template = EmailTemplate::where('name', $emailTemplate)->first();
        if (isset($template) && !empty($template)) {

            $lang = company_setting('defaultLanguage',$usr->id) ?? 'en';

            // get email content language base
            $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $lang)->first();

            if ($content) {
                $content->from = $template->from;
            } else {
                return [
                    'is_success' => false,
                    'error' => __('Email template content not found'),
                ];
            }

            if (!empty($content->content)) {
                $content->content = self::replaceVariable($content->content, $obj);
                $content->subject = self::replaceVariable($content->subject, $obj);

                // send email
                $companySettings = getCompanyAllSetting($user_id);
                if (!empty($companySettings['email_fromAddress'] ?? '')) {
                    if (!empty($user_id)) {
                        $setconfing = SetConfigEmail($user_id);
                    } else {
                        $setconfing = SetConfigEmail();
                    }
                    if ($setconfing == true) {
                        try {
                            Mail::to($mailTo)->send(new CommonEmailTemplate($content, $user_id));
                        } catch (\Exception $e) {
                            $error = $e->getMessage();
                        }
                    } else {
                        $error = __('Something went wrong please try again ');
                    }
                } else {
                    $error = __('E-Mail has been not sent due to SMTP configuration');
                }

                if (isset($error)) {
                    $arReturn = [
                        'is_success' => false,
                        'error' => $error,
                    ];
                } else {
                    $arReturn = [
                        'is_success' => true,
                        'error' => false,
                    ];
                }
            } else {
                $arReturn = [
                    'is_success' => false,
                    'error' => __('Mail not send, email is empty'),
                ];
            }
            return $arReturn;
        } else {
            return [
                'is_success' => false,
                'error' => __('Mail not send, email not found'),
            ];
        }
    }

     public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{app_name}',
            '{app_url}',
            '{company_name}',

            '{name}',
            '{email}',
            '{password}',

            '{item_name}',

            '{file_name}',
            '{file_size}',
            '{download_link}',

            '{appointment_name}',
            '{appointment_user_name}',
            '{appointment_user_email}',
            '{appointment_date}',
            '{appointment_time}',
            '{appointment_number}',
            '{appointment_status}',
            '{callback_date}',
            '{callback_time}',
            '{callback_reason}',
            '{callback_status}',

            '{deal_name}',
            '{deal_pipeline}',
            '{deal_stage}',
            '{deal_status}',
            '{deal_price}',
            '{deal_old_stage}',
            '{deal_new_stage}',

            '{task_name}',
            '{task_priority}',
            '{task_status}',

            '{lead_name}',
            '{lead_email}',
            '{lead_pipeline}',
            '{lead_stage}',
            '{lead_old_stage}',
            '{lead_new_stage}',

            '{lead_email_subject}',
            '{lead_email_description}',

            '{deal_email_subject}',
            '{deal_email_description}',

            '{tracking_id}',
            '{tracking_url}',
            '{package_title}',


            '{candidate_name}',
            '{candidate_email}',
            '{job_title}',
            '{tracking_link}',
            '{position}',
            '{salary}',
            '{start_date}',
            '{download_url}',

            '{invoice_id}',
            '{invoice_tenant}',
            '{invoice_status}',
            '{invoice_sub_total}',
            '{created_at}',

            '{doctor_name}',
            '{doctor_email}',
            '{doctor_id}',
            '{specialization}',
            '{patient_name}',
            '{patient_email}',
            '{patient_id}',
            '{bed_number}',
            '{ward_name}',
            '{bed_type}',
            '{admission_date}',
            '{discharge_date}',

            '{ticket_name}',
            '{ticket_id}',
            '{ticket_url}',
            '{reply_description}',

            '{child_name}',
            '{parent_name}',
            '{inquiry_date}',
            '{inquiry_status}',
            '{parent_email}',
            '{login_link}',

            '{request_customer_name}',
            '{request_customer_email}',
            '{request_customer_phone}',
            '{request_date}',
            '{request_time}',
            '{request_location}',
            '{request_pickup_point}',
            '{request_category_type}',
            '{request_category}',
        ];
        $arrValue    = [
            'app_name' => '-',
            'app_url' => '-',
            'company_name' => '-',

            'name' => '-',
            'email' => '-',
            'password' => '-',

            'item_name' => '-',

            'file_name' => '-',
            'file_size' => '-',
            'download_link' => '-',


            'appointment_name' => '-',
            'appointment_user_name' => '-',
            'appointment_user_email' => '-',
            'appointment_date' => '-',
            'appointment_time' => '-',
            'appointment_number' => '-',
            'appointment_status' => '-',
            'callback_date' => '-',
            'callback_time' => '-',
            'callback_reason' => '-',
            'callback_status' => '-',

            'deal_name' => '-',
            'deal_pipeline' => '-',
            'deal_stage' => '-',
            'deal_status' => '-',
            'deal_price' => '-',
            'deal_old_stage' => '-',
            'deal_new_stage' => '-',

            'task_name' => '-',
            'task_priority' => '-',
            'task_status' => '-',

            'lead_name' => '-',
            'lead_email' => '-',
            'lead_pipeline' => '-',
            'lead_stage' => '-',
            'lead_old_stage' => '-',
            'lead_new_stage' => '-',

            'lead_email_subject' => '-',
            'lead_email_description' => '-',

            'deal_email_subject' => '-',
            'deal_email_description' => '-',

            'tracking_id' => '-',
            'tracking_url' => '-',
            'package_title' => '-',

            'candidate_name' => '-',
            'candidate_email' => '-',
            'job_title' => '-',
            'tracking_link' => '-',
            'position' => '-',
            'salary' => '-',
            'start_date' => '-',
            'download_url' => '-',

            'invoice_id' => '-',
            'invoice_tenant' => '-',
            'invoice_status' => '-',
            'invoice_sub_total' => '-',
            'created_at' => '-',

            'doctor_name' => '-',
            'doctor_email' => '-',
            'doctor_id' => '-',
            'specialization' => '-',
            'patient_name' => '-',
            'patient_email' => '-',
            'patient_id' => '-',
            'bed_number' => '-',
            'ward_name' => '-',
            'bed_type' => '-',
            'admission_date' => '-',
            'discharge_date' => '-',

            'ticket_name' => '-',
            'ticket_id' => '-',
            'ticket_url' => '-',
            'reply_description' => '-',

            'child_name' => '-',
            'parent_name' => '-',
            'inquiry_date' => '-',
            'inquiry_status' => '-',
            'parent_email' => '-',
            'login_link' => '-',

            'request_customer_name'     => '-',
            'request_customer_email'    => '-',
            'request_customer_phone'    => '-',
            'request_date'              => '-',
            'request_time'              => '-',
            'request_location'          => '-',
            'request_pickup_point'      => '-',
            'request_category_type'     => '-',
            'request_category'          => '-',
        ];

        foreach ($obj as $key => $val) {

            $arrValue[$key] = $val;
        }
        $arrValue['app_name']     = env('APP_NAME');
        if (is_null($arrValue['company_name']) || $arrValue['company_name'] == '-') {
            $companySettings = getCompanyAllSetting();
            $arrValue['company_name'] = $companySettings['company_name'] ?? '--';
        }
        $arrValue['app_url']      = '<a href="' . env('APP_URL') . '" target="_blank">' . env('APP_URL') . '</a>';


        return str_replace($arrVariable, array_values($arrValue), $content);
    }
}
