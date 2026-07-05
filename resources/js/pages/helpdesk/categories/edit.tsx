import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { EditHelpdeskCategoryProps, EditHelpdeskCategoryFormData } from './types';

export default function Edit({ category, onSuccess }: EditHelpdeskCategoryProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm<EditHelpdeskCategoryFormData>({
        name: category.name,
        description: category.description || '',
        color: category.color,
        is_active: category.is_active
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('helpdesk-categories.update', category.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit Helpdesk Category')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="edit_name">{t('Name')}</Label>
                    <Input
                        id="edit_name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter category name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <Label htmlFor="edit_description">{t('Description')}</Label>
                    <Textarea
                        id="edit_description"
                        value={data.description || ''}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter category description')}
                        rows={3}
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="edit_color">{t('Color')}</Label>
                        <Input
                            id="edit_color"
                            type="color"
                            value={data.color}
                            onChange={(e) => setData('color', e.target.value)}
                        />
                        <InputError message={errors.color} />
                    </div>
                    <div>
                        <Label htmlFor="edit_is_active">{t('Status')}</Label>
                        <Select value={data.is_active ? "1" : "0"} onValueChange={(value) => setData('is_active', value === "1")}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">{t('Active')}</SelectItem>
                                <SelectItem value="0">{t('Inactive')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.is_active} />
                    </div>
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