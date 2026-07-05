import React, { useState, useEffect } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import MediaLibraryModal from './MediaLibraryModal';
import { Image as ImageIcon, X } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

interface MediaPickerProps {
  label?: string;
  value?: string | string[];
  onChange: (value: string | string[]) => void;
  multiple?: boolean;
  placeholder?: string;
  showPreview?: boolean;
  readOnly?: boolean;
  disabled?: boolean;
  id?: string;
  required?: boolean;
}

export default function MediaPicker({
  label,
  value = '',
  onChange,
  multiple = false,
  placeholder = 'Select image...',
  showPreview = true,
  readOnly = false,
  disabled = false,
  id,
  required
}: MediaPickerProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleSelect = (selectedUrls: string | string[]) => {
    onChange(selectedUrls);
  };

  const handleClear = () => {
    onChange(multiple ? [] : '');
  };

  // Handle both single and multiple values
  const safeValue = multiple
    ? (Array.isArray(value) ? value : (value ? [value] : []))
    : (Array.isArray(value) ? (value[0] || '') : (value || ''));

  // Process the media URL for preview
  const getDisplayUrl = (url: string) => {
    return getImagePath(url);
  };

  // Get file type from URL
  const getFileType = (url: string) => {
    const extension = url.split('.').pop()?.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension || '')) {
      return 'image';
    }
    if (['mp4', 'webm', 'ogg', 'mov', 'avi'].includes(extension || '')) {
      return 'video';
    }
    return 'file';
  };

  const mediaUrls = multiple
    ? (Array.isArray(safeValue) ? safeValue.filter(Boolean).map(getDisplayUrl) : [])
    : (safeValue ? [getDisplayUrl(safeValue as string)] : []);

  return (
    <div>
      {label && <Label htmlFor={id} required={required}>{label}</Label>}

      <div className="flex gap-2">
        <Input
          id={id}
          value={multiple ? (Array.isArray(safeValue) ? safeValue.join(', ') : '') : (safeValue as string)}
          onChange={(e) => !multiple && onChange(e.target.value)}
          placeholder={placeholder}
          readOnly={readOnly || multiple}
          required={required}
        />
        <Button
          type="button"
          variant="outline"
          onClick={() => setIsModalOpen(true)}
          disabled={readOnly || disabled}
        >
          <ImageIcon className="h-4 w-4 mr-2" />
          Browse
        </Button>
        {((multiple && Array.isArray(safeValue) && safeValue.length > 0) || (!multiple && safeValue)) && (
          <Button
            type="button"
            variant="outline"
            size="icon"
            onClick={handleClear}
            disabled={readOnly || disabled}
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>

      {/* Preview */}
      {showPreview && mediaUrls.length > 0 && (
        <div className="grid grid-cols-4 gap-2 mt-2">
          {mediaUrls.map((url, index) => {
            const fileType = getFileType(url);
            return (
              <div key={index} className="relative">
                {fileType === 'image' && (
                  <img
                    src={url}
                    alt={`Preview ${index + 1}`}
                    className="w-full h-20 object-cover rounded border"
                  />
                )}
                {fileType === 'video' && (
                  <video
                    src={url}
                    className="w-full h-20 object-cover rounded border"
                    controls={false}
                    muted
                  />
                )}
                {fileType === 'file' && (
                  <div className="w-full h-20 flex items-center justify-center bg-gray-100 rounded border">
                    <div className="text-center">
                      <svg className="w-6 h-6 mx-auto mb-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clipRule="evenodd" />
                      </svg>
                      <span className="text-xs text-gray-500">
                        {url.split('.').pop()?.toUpperCase()}
                      </span>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      <MediaLibraryModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSelect={handleSelect}
        multiple={multiple}
      />
    </div>
  );
}