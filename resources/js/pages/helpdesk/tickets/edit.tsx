import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { RichTextEditor } from "@/components/ui/rich-text-editor";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { EditHelpdeskTicketProps, EditHelpdeskTicketFormData } from './types';

export default function Edit({ ticket, onSuccess }: { ticket: any; onSuccess: () => void }) {
    const { categories, companies, auth } = usePage<any>().props;
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditHelpdeskTicketFormData>({
        title: ticket.title || '',
        description: ticket.description || '',
        status: ticket.status || 'open',
        priority: ticket.priority || 'medium',
        category_id: ticket.category_id || 0,

    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('helpdesk-tickets.update', ticket.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Helpdesk Ticket')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="edit_title">{t('Title')}</Label>
                    <Input
                        id="edit_title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter ticket title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label htmlFor="edit_description">{t('Description')}</Label>
                    <RichTextEditor
                        content={data.description || ''}
                        onChange={(value) => setData('description', value)}
                        placeholder={t('Describe your issue in detail')}
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="edit_status">{t('Status')}</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value as any)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="open">{t('Open')}</SelectItem>
                                <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                                <SelectItem value="resolved">{t('Resolved')}</SelectItem>
                                <SelectItem value="closed">{t('Closed')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div>
                        <Label htmlFor="edit_priority">{t('Priority')}</Label>
                        <Select value={data.priority} onValueChange={(value) => setData('priority', value as any)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="low">{t('Low')}</SelectItem>
                                <SelectItem value="medium">{t('Medium')}</SelectItem>
                                <SelectItem value="high">{t('High')}</SelectItem>
                                <SelectItem value="urgent">{t('Urgent')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>
                </div>

                <div>
                    <Label htmlFor="edit_category_id">{t('Category')}</Label>
                    <Select value={data.category_id?.toString()} onValueChange={(value) => setData('category_id', parseInt(value))}>
                        <SelectTrigger>
                            <SelectValue placeholder={categories?.length === 0 ? "No categories available" : "Select category"} />
                        </SelectTrigger>
                        <SelectContent>
                            {categories?.map((category: any) => (
                                <SelectItem key={category.id} value={category.id.toString()}>
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category_id} />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}