import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import InputError from "@/components/ui/input-error";
import { ChangePasswordProps, ChangePasswordFormData } from './types';

export default function ChangePassword({ user, onSuccess }: ChangePasswordProps) {
    const { t } = useTranslation();
    const { data, setData, patch, processing, errors } = useForm<ChangePasswordFormData>({
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('users.change-password', user.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Change Password')} - {user.name}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="change_password">{t('Password')}</Label>
                    <Input
                        id="change_password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder={t('Enter new password')}
                        required
                    />
                    <InputError message={errors.password} />
                </div>
                <div>
                    <Label htmlFor="change_password_confirmation">{t('Confirm Password')}</Label>
                    <Input
                        id="change_password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder={t('Confirm new password')}
                        required
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Changing...') : t('Change Password')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}