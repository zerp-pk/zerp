import { useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { RichTextEditor } from "@/components/ui/rich-text-editor";
import { Send } from 'lucide-react';
import MediaPicker from "@/components/MediaPicker";
import { ReplyFormProps } from './types';

export default function ReplyForm({ ticketId, onReplyAdded, disabled }: ReplyFormProps) {
    const { t } = useTranslation();
    const [message, setMessage] = useState('');
    const [attachments, setAttachments] = useState<string[]>([]);
    const [isInternal, setIsInternal] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editorKey, setEditorKey] = useState(0);
    const { auth } = usePage<any>().props;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const isMessageEmpty = !message || message.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, '').trim() === '';
        if (isMessageEmpty || isSubmitting) return;

        setIsSubmitting(true);
        
        try {
            const payload = {
                message,
                is_internal: isInternal,
                attachments: attachments || null
            };

            const response = await fetch(route('helpdesk-replies.store', ticketId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            
            if (data.success) {
                setMessage('');
                setAttachments([]);
                setIsInternal(false);
                setEditorKey(prev => prev + 1); // Force re-render to clear editor
                onReplyAdded(data.reply);
            } else {
                console.error('Failed to send reply');
            }
        } catch (error) {
            console.error('Error sending reply:', error);
        } finally {
            setIsSubmitting(false);
        }
    };



    return (
        <div className="border-t bg-gray-50/50 p-3">
            <div className="space-y-2">
                <div className="relative">
                    <RichTextEditor
                        key={editorKey}
                        content={message}
                        onChange={(content) => setMessage(content)}
                        placeholder={t('Type your message...')}
                        disabled={disabled || isSubmitting}
                        className="min-h-[80px]"
                        onKeyDown={(e: React.KeyboardEvent) => {
                            e.stopPropagation();
                        }}
                    />
                </div>
                
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3 flex-1">
                        <div className="flex items-center gap-1">
                            <MediaPicker
                                label=""
                                value={attachments}
                                onChange={(value) => setAttachments(Array.isArray(value) ? value : [value].filter(Boolean))}
                                multiple={true}
                                placeholder={t('Attach')}
                                showPreview={false}
                                disabled={disabled || isSubmitting}
                            />
                        </div>
                        
                        {auth.user?.type === 'superadmin' && (
                            <div className="flex items-center gap-1.5">
                                <Checkbox
                                    id="is_internal"
                                    checked={isInternal}
                                    onCheckedChange={(checked) => setIsInternal(!!checked)}
                                    disabled={disabled || isSubmitting}
                                    className="h-4 w-4"
                                />
                                <label htmlFor="is_internal" className="text-xs text-gray-600 cursor-pointer whitespace-nowrap">
                                    {t('Internal')}
                                </label>
                            </div>
                        )}
                    </div>
                    
                    <Button
                        type="button"
                        onClick={handleSubmit}
                        disabled={(!message || message.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, '').trim() === '') || disabled || isSubmitting}
                        size="sm"
                        className="flex items-center gap-1.5 px-4 py-2 h-8"
                    >
                        <Send className="h-3.5 w-3.5" />
                        <span className="text-xs font-medium">
                            {isSubmitting ? t('Sending...') : t('Send')}
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    );
}