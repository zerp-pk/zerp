import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import { BrandProvider } from '@/contexts/brand-context';
import { useFlashMessages } from '@/hooks/useFlashMessages';

export default function AuthLayout({
    children,
    title,
    description,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description: string;
}) {
    useFlashMessages();
    return (
        <BrandProvider>
            <AuthLayoutTemplate title={title} description={description} {...props}>
                {children}
            </AuthLayoutTemplate>
        </BrandProvider>
    );
}