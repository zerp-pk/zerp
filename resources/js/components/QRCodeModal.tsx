import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { QrCode } from 'lucide-react';
import QRCode from 'qrcode';

interface QRCodeModalProps {
  isOpen: boolean;
  onClose: () => void;
  url: string;
  title: string;
}

export function QRCodeModal({ isOpen, onClose, url, title }: QRCodeModalProps) {
  const { t } = useTranslation();
  const [qrUrl, setQrUrl] = useState('');

  useEffect(() => {
    if (isOpen && url) {
      QRCode.toDataURL(url, {
        width: 256,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#ffffff'
        }
      })
      .then(dataUrl => {
        setQrUrl(dataUrl);
      })
      .catch(err => {
        console.error('Error generating QR code:', err);
      });
    }
  }, [isOpen, url]);

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md max-h-[90vh] overflow-y-auto">
        <DialogHeader className="pb-4 border-b">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary/10 rounded-lg">
              <QrCode className="h-5 w-5 text-primary" />
            </div>
            <div>
              <DialogTitle className="text-xl font-semibold">{title}</DialogTitle>
              <p className="text-sm text-muted-foreground">{t('Scan to submit work request')}</p>
            </div>
          </div>
        </DialogHeader>

        <div className="overflow-y-auto flex-1 p-4 space-y-6">
          <div className="flex flex-col items-center space-y-4">
            <div className="w-64 h-64 border border-gray-300 rounded flex items-center justify-center bg-background">
              {qrUrl ? (
                <img src={qrUrl} alt="QR Code" className="w-full h-full object-contain" />
              ) : (
                <p className="text-gray-500">{t('Loading QR code...')}</p>
              )}
            </div>
            <p className="text-sm text-gray-600 text-center">
              {t('Point your camera at the QR code to open the work request form')}
            </p>

          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}