import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Shuffle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface GenerateCodeProps {
    onGenerate: (code: string) => void;
    disabled?: boolean;
    length?: number;
    prefix?: string;
    includeNumbers?: boolean;
    includeLetters?: boolean;
}

export function GenerateCode({ 
    onGenerate, 
    disabled = false, 
    length = 8, 
    prefix = '',
    includeNumbers = true,
    includeLetters = true
}: GenerateCodeProps) {
    const { t } = useTranslation();
    const [isGenerating, setIsGenerating] = useState(false);

    const generateRandomCode = () => {
        setIsGenerating(true);
        
        // Build character set based on options
        let characters = '';
        if (includeLetters) {
            characters += 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Excluding I, O for clarity
        }
        if (includeNumbers) {
            characters += '23456789'; // Excluding 0, 1 for clarity
        }
        
        // Fallback to alphanumeric if no options selected
        if (!characters) {
            characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        }
        
        let result = prefix;
        
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        
        // Add a small delay for better UX
        setTimeout(() => {
            onGenerate(result);
            setIsGenerating(false);
        }, 200);
    };

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={generateRandomCode}
            disabled={disabled || isGenerating}
            className="shrink-0"
        >
            <Shuffle className={`h-4 w-4 mr-1 ${isGenerating ? 'animate-spin' : ''}`} />
            {isGenerating ? t('Generating...') : t('Generate')}
        </Button>
    );
}