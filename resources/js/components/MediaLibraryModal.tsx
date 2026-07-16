import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from './ui/dialog';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Badge } from './ui/badge';
import { Upload, Image as ImageIcon, Search, Plus, Check, Home } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useMediaLibrary } from '@/hooks/use-media-library';

interface MediaLibraryModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSelect: (url: string | string[]) => void;
  multiple?: boolean;
}

export default function MediaLibraryModal({
  isOpen,
  onClose,
  onSelect,
  multiple = false
}: MediaLibraryModalProps) {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const canCreateMedia = permissions.includes('create-media') || permissions.includes('manage-media');

  const {
    media,
    directories,
    breadcrumbs,
    currentDirectoryId,
    pagination,
    loading,
    uploading,
    searchTerm,
    setSearchTerm,
    navigateToDirectory,
    goToBreadcrumb,
    createDirectory,
    uploadFiles,
    setPage,
  } = useMediaLibrary({ perPage: 18, enabled: isOpen });

  const [selectedItems, setSelectedItems] = useState<string[]>([]);
  const [dragActive, setDragActive] = useState(false);
  const [showCreateDirectory, setShowCreateDirectory] = useState(false);
  const [newDirectoryName, setNewDirectoryName] = useState('');

  useEffect(() => {
    if (isOpen) {
      setSearchTerm('');
      setSelectedItems([]);
      navigateToDirectory(null);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen]);

  const handleFileUpload = (files: FileList) => uploadFiles(files);

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileUpload(e.dataTransfer.files);
    }
  };

  // NOTE: contract is frozen - every MediaPicker consumer across the app expects the bare
  // file_name string (single) / string[] of file_names (multiple), not a URL or numeric id.
  const handleSelect = (fileName: string) => {
    if (multiple) {
      setSelectedItems(prev =>
        prev.includes(fileName)
          ? prev.filter(item => item !== fileName)
          : [...prev, fileName]
      );
    } else {
      onSelect(fileName);
      onClose();
    }
  };

  const handleConfirmSelection = () => {
    if (multiple && selectedItems.length > 0) {
      onSelect(selectedItems);
      onClose();
    }
  };

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) return <ImageIcon className="h-8 w-8" />;
    if (mimeType.includes('pdf')) return <div className="h-8 w-8 bg-red-500 rounded text-white text-sm flex items-center justify-center font-bold">PDF</div>;
    if (mimeType.includes('word') || mimeType.includes('document')) return <div className="h-8 w-8 bg-blue-500 rounded text-white text-sm flex items-center justify-center font-bold">DOC</div>;
    if (mimeType.includes('csv') || mimeType.includes('spreadsheet')) return <div className="h-8 w-8 bg-green-500 rounded text-white text-sm flex items-center justify-center font-bold">CSV</div>;
    if (mimeType.startsWith('video/')) return <div className="h-8 w-8 bg-purple-500 rounded text-white text-sm flex items-center justify-center font-bold">VID</div>;
    if (mimeType.startsWith('audio/')) return <div className="h-8 w-8 bg-orange-500 rounded text-white text-sm flex items-center justify-center font-bold">AUD</div>;
    return <div className="h-8 w-8 bg-gray-500 rounded text-white text-sm flex items-center justify-center font-bold">FILE</div>;
  };

  const handleCreateDirectory = async () => {
    const ok = await createDirectory(newDirectoryName, currentDirectoryId);
    if (ok) {
      setNewDirectoryName('');
      setShowCreateDirectory(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl h-[90vh] flex flex-col">
        <DialogHeader className="pb-4">
          <DialogTitle className="flex items-center gap-2">
            <ImageIcon className="h-5 w-5" />
            {t('Media Library')}
            {pagination.total > 0 && (
              <Badge variant="secondary" className="ml-2">
                {pagination.total}
              </Badge>
            )}
          </DialogTitle>
          <DialogDescription>
            {t('Upload new files to your media library')}
          </DialogDescription>
        </DialogHeader>

        <div className="flex-1 flex flex-col space-y-4 min-h-0">
          {/* Breadcrumb + Directory Navigation */}
          <div className="flex items-center gap-2 flex-wrap">
            <Button
              variant={currentDirectoryId === null ? "default" : "outline"}
              size="sm"
              onClick={() => navigateToDirectory(null)}
            >
              <Home className="h-4 w-4 mr-1" />
              {t('Files')}
            </Button>
            {breadcrumbs.map((crumb) => (
              <React.Fragment key={crumb.id}>
                <span className="text-muted-foreground">/</span>
                <Button
                  variant={currentDirectoryId === crumb.id ? "default" : "outline"}
                  size="sm"
                  onClick={() => goToBreadcrumb(crumb.id)}
                >
                  {crumb.name}
                </Button>
              </React.Fragment>
            ))}
            {directories.map((dir) => (
              <Button
                key={dir.id}
                variant="outline"
                size="sm"
                onClick={() => navigateToDirectory(dir.id)}
              >
                {dir.name}
              </Button>
            ))}
            {canCreateMedia && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowCreateDirectory(true)}
              >
                <Plus className="h-4 w-4" />
              </Button>
            )}
          </div>

          {/* Create Directory */}
          {showCreateDirectory && (
            <div className="p-3 border rounded-lg bg-muted/30">
              <div className="flex gap-2">
                <Input
                  placeholder={t('Enter directory name...')}
                  value={newDirectoryName}
                  onChange={(e) => setNewDirectoryName(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleCreateDirectory()}
                />
                <Button onClick={handleCreateDirectory} size="sm">
                  {t('Create')}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setShowCreateDirectory(false);
                    setNewDirectoryName('');
                  }}
                >
                  {t('Cancel')}
                </Button>
              </div>
            </div>
          )}

          {/* Header with Search and Upload */}
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder={t('Search media files...')}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>

            <div className="flex gap-2">
              <Input
                type="file"
                multiple
                onChange={(e) => e.target.files && handleFileUpload(e.target.files)}
                className="hidden"
                id="file-upload"
              />
              <Button
                type="button"
                variant="outline"
                onClick={() => document.getElementById('file-upload')?.click()}
                disabled={uploading}
                size="sm"
              >
                <Plus className="h-4 w-4 mr-2" />
                {uploading ? t('Uploading...') : t('Upload Files')}
              </Button>
            </div>
          </div>

          {/* Stats and Selection Info */}
          <div className="flex items-center justify-between text-sm text-muted-foreground bg-muted/30 px-3 py-2 rounded-md">
            <span>
              {t('files', { count: pagination.total })} • {t('Page')} {pagination.current_page} {t('of')} {pagination.last_page}
            </span>
            {multiple && selectedItems.length > 0 && (
              <Badge variant="default" className="text-xs">
                {t('selected', { count: selectedItems.length })}
              </Badge>
            )}
          </div>

          {/* Media Grid */}
          <div className="border rounded-lg bg-muted/10 flex flex-col flex-1 min-h-0">
            {loading ? (
              <div className="flex-1 flex items-center justify-center">
                <div className="text-center">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                  <p className="text-muted-foreground">{t('Loading media...')}</p>
                </div>
              </div>
            ) : media.length === 0 ? (
              <div className="flex-1 flex items-center justify-center py-16">
                <div className="text-center max-w-sm">
                  <div
                    className={`mx-auto w-24 h-24 border-2 border-dashed rounded-xl flex items-center justify-center mb-6 transition-colors ${
                      dragActive ? 'border-primary bg-primary/5' : 'border-muted-foreground/25'
                    }`}
                    onDragEnter={handleDrag}
                    onDragLeave={handleDrag}
                    onDragOver={handleDrag}
                    onDrop={handleDrop}
                  >
                    <Upload className="h-10 w-10 text-muted-foreground" />
                  </div>

                  <div className="space-y-3 mb-6">
                    <h3 className="text-lg font-semibold">{t('No media files found')}</h3>
                    {searchTerm && (
                      <p className="text-sm text-muted-foreground">
                        {t('No results found for ')} <span className="font-medium text-foreground">"{searchTerm}"</span>
                      </p>
                    )}
                    <p className="text-sm text-muted-foreground">
                      {searchTerm ? t('Try a different search term or upload new files') : t('Get started by uploading your first file')}
                    </p>
                  </div>

                  <Button
                    type="button"
                    onClick={() => document.getElementById('file-upload')?.click()}
                    disabled={uploading}
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    {t('Upload Files')}
                  </Button>
                </div>
              </div>
            ) : (
              <div className="flex-1 flex flex-col overflow-hidden">
                <div className="flex-1 overflow-y-auto p-4">
                  <div className="grid grid-cols-5 gap-3">
                    {media.map((item) => (
                      <div
                        key={item.id}
                        className={`relative group cursor-pointer rounded-lg overflow-hidden transition-all hover:scale-105 ${
                          selectedItems.includes(item.file_name)
                            ? 'ring-2 ring-primary shadow-lg'
                            : 'hover:shadow-md border border-border hover:border-primary/50'
                        }`}
                        onClick={() => handleSelect(item.file_name)}
                      >
                        <div className="relative aspect-square bg-muted">
                          {item.mime_type.startsWith('image/') ? (
                            <img
                              src={item.thumb_url}
                              alt={item.name}
                              className="w-full h-full object-cover"
                              onError={(e) => {
                                e.currentTarget.src = item.url;
                              }}
                            />
                          ) : (
                            <div className="w-full h-full flex flex-col items-center justify-center p-4">
                              <div className="mb-2">
                                {getFileIcon(item.mime_type)}
                              </div>
                              <div className="text-xs text-center font-medium text-muted-foreground truncate w-full">
                                {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                              </div>
                            </div>
                          )}

                          {/* Selection Indicator */}
                          {selectedItems.includes(item.file_name) && (
                            <div className="absolute inset-0 bg-primary/30 flex items-center justify-center">
                              <div className="bg-primary text-primary-foreground rounded-full p-1.5">
                                <Check className="h-4 w-4" />
                              </div>
                            </div>
                          )}

                          {/* File Name Tooltip */}
                          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <p className="text-xs text-white truncate" title={item.name}>
                              {item.name}
                            </p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="flex-shrink-0 flex justify-between items-center pt-4 border-t">
              <div className="text-sm text-muted-foreground">
                {t('Showing page')} {pagination.current_page} {t('of')} {pagination.last_page} ({pagination.total} {t('files')})
              </div>
              <div className="flex gap-1">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={pagination.current_page === 1}
                  onClick={() => setPage(Math.max(pagination.current_page - 1, 1))}
                >
                  {t('Previous')}
                </Button>
                {Array.from({ length: Math.min(pagination.last_page, 5) }, (_, i) => {
                  let page;
                  const totalPages = pagination.last_page;
                  const currentPage = pagination.current_page;
                  if (totalPages <= 5) {
                    page = i + 1;
                  } else if (currentPage <= 3) {
                    page = i + 1;
                  } else if (currentPage >= totalPages - 2) {
                    page = totalPages - 4 + i;
                  } else {
                    page = currentPage - 2 + i;
                  }

                  return (
                    <Button
                      key={page}
                      variant={pagination.current_page === page ? 'default' : 'outline'}
                      size="sm"
                      className="w-8 h-8 p-0"
                      onClick={() => setPage(page)}
                    >
                      {page}
                    </Button>
                  );
                })}
                <Button
                  variant="outline"
                  size="sm"
                  disabled={pagination.current_page === pagination.last_page}
                  onClick={() => setPage(Math.min(pagination.current_page + 1, pagination.last_page))}
                >
                  {t('Next')}
                </Button>
              </div>
            </div>
          )}

          {/* Multiple Selection Footer */}
          {multiple && selectedItems.length > 0 && (
            <div className="flex-shrink-0 flex justify-between items-center pt-4 border-t">
              <span className="text-sm text-muted-foreground">
                {selectedItems.length} {t('files selected')}
              </span>
              <div className="flex gap-2">
                <Button variant="outline" onClick={() => setSelectedItems([])}>
                  {t('Clear Selection')}
                </Button>
                <Button onClick={handleConfirmSelection}>
                  {t('Select Files')}
                </Button>
              </div>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
