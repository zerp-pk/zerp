import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface TranslationItemProps {
    translationKey: string;
    value: string;
    onChange: (key: string, value: string) => void;
}

export function TranslationItem({ translationKey, value, onChange }: TranslationItemProps) {
    return (
        <div className="grid grid-cols-5 gap-4 p-3 border-b hover:bg-muted/30 transition-colors">
            <div className="col-span-2">
                <div className="text-sm font-medium text-foreground truncate" title={translationKey}>
                    {translationKey}
                </div>
            </div>
            <div className="col-span-3">
                {value.length > 100 ? (
                    <Textarea
                        value={value}
                        onChange={(e) => onChange(translationKey, e.target.value)}
                        className="min-h-[60px] text-sm resize-none"
                        rows={2}
                        placeholder="Enter translation value..."
                    />
                ) : (
                    <Input
                        value={value}
                        onChange={(e) => onChange(translationKey, e.target.value)}
                        className="text-sm"
                        placeholder="Enter translation value..."
                    />
                )}
            </div>
        </div>
    );
}