import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { RefreshCw } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface CreateLanguageModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSuccess?: () => void;
}

export function CreateLanguageModal({ open, onOpenChange, onSuccess }: CreateLanguageModalProps) {
    const { t } = useTranslation();
    const [formData, setFormData] = useState({
        code: '',
        name: '',
        countryCode: ''
    });
    const [isLoading, setIsLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        try {
            const response = await fetch(route('languages.create'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                toast.success(t('Language created successfully'));
                setFormData({ code: '', name: '', countryCode: '' });
                onOpenChange(false);
                onSuccess?.();
                // Reload page to update language list
                window.location.reload();
            } else {
                toast.error(data.error || t('Failed to create language'));
            }
        } catch (error) {
            toast.error(t('Failed to create language'));
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('Create Language')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="code">{t('Language Code')}</Label>
                        <Input
                            id="code"
                            placeholder={t('e.g., fr, de, ja')}
                            value={formData.code}
                            onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                            required
                        />
                    </div>
                    <div>
                        <Label htmlFor="name">{t('Language Name')}</Label>
                        <Input
                            id="name"
                            placeholder={t('e.g., Français, Deutsch, 日本語')}
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            required
                        />
                    </div>
                    <div>
                        <Label htmlFor="countryCode">{t('Country Code')}</Label>
                        <Input
                            id="countryCode"
                            placeholder={t('e.g., FR, DE, JP')}
                            maxLength={2}
                            value={formData.countryCode}
                            onChange={(e) => setFormData({ ...formData, countryCode: e.target.value.toUpperCase() })}
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? (
                                <>
                                    <RefreshCw className="h-4 w-4 animate-spin mr-2" />
                                    {t('Creating...')}
                                </>
                            ) : (
                                t('Create Language')
                            )}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
