import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { Upload, Search, X, Plus, Info, Copy, Download, MoreHorizontal, Image as ImageIcon, Calendar, HardDrive, Edit, Trash2, Folder, FolderOpen, Home, FolderInput } from 'lucide-react';
import { useMediaLibrary } from '@/hooks/use-media-library';
import type { MediaItem, MediaDirectory, Breadcrumb } from '@/types/media';

export default function MediaLibraryDemo() {
  const { t } = useTranslation();

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
    renameDirectory,
    deleteDirectory,
    uploadFiles,
    deleteMedia,
    bulkDeleteMedia,
    renameMedia,
    moveMedia,
    setPage,
  } = useMediaLibrary({ perPage: 24 });

  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const [showCreateDirectory, setShowCreateDirectory] = useState(false);
  const [newDirectoryName, setNewDirectoryName] = useState('');
  const [editingDirectory, setEditingDirectory] = useState<number | null>(null);
  const [editDirectoryName, setEditDirectoryName] = useState('');

  const [infoModalOpen, setInfoModalOpen] = useState(false);
  const [selectedMediaInfo, setSelectedMediaInfo] = useState<MediaItem | null>(null);

  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  const [renameTarget, setRenameTarget] = useState<MediaItem | null>(null);
  const [renameValue, setRenameValue] = useState('');

  const [moveTarget, setMoveTarget] = useState<MediaItem | null>(null);
  const [moveDirId, setMoveDirId] = useState<number | null>(null);
  const [moveDirs, setMoveDirs] = useState<MediaDirectory[]>([]);
  const [moveBreadcrumbs, setMoveBreadcrumbs] = useState<Breadcrumb[]>([]);
  const [moveLoading, setMoveLoading] = useState(false);

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
      setIsUploadModalOpen(false);
    }
  };

  const handleCreateDirectory = async () => {
    const ok = await createDirectory(newDirectoryName, currentDirectoryId);
    if (ok) {
      setNewDirectoryName('');
      setShowCreateDirectory(false);
    }
  };

  const handleUpdateDirectory = async () => {
    if (!editingDirectory) return;
    const ok = await renameDirectory(editingDirectory, editDirectoryName);
    if (ok) {
      setEditingDirectory(null);
      setEditDirectoryName('');
    }
  };

  const handleDeleteDirectory = async (id: number) => {
    if (!confirm(t('Are you sure you want to delete this directory?'))) return;
    await deleteDirectory(id);
  };

  const handleCopyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    toast.success(t('File URL copied to clipboard'));
  };

  const handleDownload = (id: number, filename: string) => {
    const link = document.createElement('a');
    link.href = route('media.download', id);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handleShowInfo = (item: MediaItem) => {
    setSelectedMediaInfo(item);
    setInfoModalOpen(true);
  };

  const toggleSelected = (id: number) => {
    setSelectedIds(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);
  };

  const handleBulkDelete = async () => {
    if (selectedIds.length === 0) return;
    if (!confirm(t('Delete {{count}} selected file(s)?', { count: selectedIds.length }))) return;
    const ok = await bulkDeleteMedia(selectedIds);
    if (ok) setSelectedIds([]);
  };

  const openRenameDialog = (item: MediaItem) => {
    setRenameTarget(item);
    setRenameValue(item.name);
  };

  const handleRenameConfirm = async () => {
    if (!renameTarget) return;
    const ok = await renameMedia(renameTarget.id, renameValue);
    if (ok) setRenameTarget(null);
  };

  const fetchMoveDirs = async (dirId: number | null) => {
    setMoveLoading(true);
    try {
      const params = new URLSearchParams();
      if (dirId) params.append('directory_id', String(dirId));
      const response = await fetch(`${route('media.index')}?${params}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await response.json();
      setMoveDirs(data.directories ?? []);
      setMoveBreadcrumbs(data.breadcrumbs ?? []);
      setMoveDirId(dirId);
    } finally {
      setMoveLoading(false);
    }
  };

  const openMoveDialog = (item: MediaItem) => {
    setMoveTarget(item);
    fetchMoveDirs(null);
  };

  const handleMoveHere = async () => {
    if (!moveTarget) return;
    const ok = await moveMedia(moveTarget.id, moveDirId);
    if (ok) setMoveTarget(null);
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatDate = (dateString: string) => new Date(dateString).toLocaleString();

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) return <ImageIcon className="h-4 w-4" />;
    if (mimeType.includes('pdf')) return <div className="h-4 w-4 bg-red-500 rounded text-white text-xs flex items-center justify-center font-bold">PDF</div>;
    if (mimeType.includes('word') || mimeType.includes('document')) return <div className="h-4 w-4 bg-primary rounded text-white text-xs flex items-center justify-center font-bold">DOC</div>;
    if (mimeType.includes('csv') || mimeType.includes('spreadsheet')) return <div className="h-4 w-4 bg-green-500 rounded text-white text-xs flex items-center justify-center font-bold">CSV</div>;
    if (mimeType.startsWith('video/')) return <div className="h-4 w-4 bg-purple-500 rounded text-white text-xs flex items-center justify-center font-bold">VID</div>;
    if (mimeType.startsWith('audio/')) return <div className="h-4 w-4 bg-orange-500 rounded text-white text-xs flex items-center justify-center font-bold">AUD</div>;
    return <div className="h-4 w-4 bg-gray-500 rounded text-white text-xs flex items-center justify-center font-bold">FILE</div>;
  };

  const totalSize = useMemo(() => media.reduce((acc, item) => acc + item.size, 0), [media]);
  const imageCount = useMemo(() => media.filter(item => item.mime_type.startsWith('image/')).length, [media]);

  const pageBreadcrumbs = [{ label: t('Media Library') }];

  return (
    <AuthenticatedLayout
      breadcrumbs={pageBreadcrumbs}
      pageTitle={t('Manage Media Library')}
      pageActions={
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => setShowCreateDirectory(true)}
          >
            <Plus className="h-4 w-4 mr-2" />
            {t('New Folder')}
          </Button>
          <Button onClick={() => setIsUploadModalOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            {t('Upload Files')}
          </Button>
        </div>
      }
    >
      <Head title={t('Media Library')} />
      <div className="space-y-6">

        {/* Breadcrumb Navigation */}
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between flex-wrap gap-2">
              <nav className="flex items-center flex-wrap space-x-1 text-sm text-muted-foreground">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => navigateToDirectory(null)}
                  className="flex items-center gap-2 h-8 px-2 hover:bg-muted hover:text-foreground"
                >
                  <Home className="h-4 w-4" />
                  {t('Media Library')}
                </Button>
                {breadcrumbs.map((crumb) => (
                  <React.Fragment key={crumb.id}>
                    <span className="mx-1">/</span>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => goToBreadcrumb(crumb.id)}
                      className="flex items-center gap-2 h-8 px-2 hover:bg-muted hover:text-foreground"
                    >
                      <Folder className="h-4 w-4 text-primary" />
                      {crumb.name}
                    </Button>
                  </React.Fragment>
                ))}
              </nav>
            </div>

            {showCreateDirectory && (
              <div className="mt-4 p-3 border rounded-lg bg-muted/30">
                <div className="flex gap-2">
                  <Input
                    placeholder={t('Directory name...')}
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

            {editingDirectory && (
              <div className="mt-4 p-3 border rounded-lg bg-muted/30">
                <div className="flex gap-2">
                  <Input
                    placeholder={t('Directory name...')}
                    value={editDirectoryName}
                    onChange={(e) => setEditDirectoryName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && handleUpdateDirectory()}
                  />
                  <Button onClick={handleUpdateDirectory} size="sm">
                    {t('Update')}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setEditingDirectory(null);
                      setEditDirectoryName('');
                    }}
                  >
                    {t('Cancel')}
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Search and Stats Bar */}
        <Card>
          <CardContent className="p-4">
            <div className="flex flex-col lg:flex-row gap-4">
              <div className="flex-1">
                <div className="relative max-w-sm">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                  <Input
                    placeholder={t('Search media files...')}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10"
                  />
                </div>
                {searchTerm && (
                  <p className="text-xs text-muted-foreground mt-1">
                    {t('Showing results for "{{term}}"', { term: searchTerm })}
                  </p>
                )}
              </div>

              <div className="flex gap-6 items-center">
                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-primary/10 rounded-md">
                    <ImageIcon className="h-4 w-4 text-primary" />
                  </div>
                  <span className="text-sm font-semibold">{pagination.total} {t('Files')}</span>
                </div>

                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-green-500/10 rounded-md">
                    <HardDrive className="h-4 w-4 text-green-600" />
                  </div>
                  <span className="text-sm font-semibold">{formatFileSize(totalSize)}</span>
                </div>

                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-primary/10 rounded-md">
                    <ImageIcon className="h-4 w-4 text-primary" />
                  </div>
                  <span className="text-sm font-semibold">{imageCount} {t('Images')}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Bulk Selection Bar */}
        {selectedIds.length > 0 && (
          <Card className="border-primary">
            <CardContent className="p-3 flex items-center justify-between">
              <span className="text-sm font-medium">
                {t('{{count}} selected', { count: selectedIds.length })}
              </span>
              <div className="flex gap-2">
                <Button variant="outline" size="sm" onClick={() => setSelectedIds([])}>
                  {t('Clear')}
                </Button>
                <Button variant="destructive" size="sm" onClick={handleBulkDelete}>
                  <Trash2 className="h-4 w-4 mr-2" />
                  {t('Delete Selected')}
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Media Grid */}
        <Card>
          <CardContent className="p-6">
            {loading ? (
              <div className="text-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground">{t('Loading media...')}</p>
              </div>
            ) : media.length === 0 && directories.length === 0 ? (
              <div className="text-center py-16">
                <div className="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
                  <ImageIcon className="h-10 w-10 text-muted-foreground" />
                </div>
                <h3 className="text-lg font-semibold mb-2">{t('No media files found')}</h3>
                <p className="text-muted-foreground mb-6">
                  {searchTerm ? t('No results found for "{{term}}"', { term: searchTerm }) : t('Get started by uploading your first file')}
                </p>
                {!searchTerm && (
                  <Button onClick={() => setIsUploadModalOpen(true)} size="lg">
                    <Plus className="h-4 w-4 mr-2" />
                    {t('Upload Files')}
                  </Button>
                )}
              </div>
            ) : (
              <>
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                  {/* Directory Cards */}
                  {directories.map((directory) => (
                    <div
                      key={`dir-${directory.id}`}
                      className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200 cursor-pointer"
                      onClick={() => navigateToDirectory(directory.id)}
                    >
                      <div className="relative aspect-square bg-gradient-to-br from-primary/10 to-primary/20 flex items-center justify-center">
                        <div className="flex flex-col items-center justify-center p-4">
                          <div className="mb-2 text-primary">
                            <Folder className="h-12 w-12" />
                          </div>
                        </div>
                        <div className="absolute inset-0 bg-black/0 group-hover:bg-primary/5 transition-all duration-200" />
                        <div className="absolute top-2 right-2">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                size="sm"
                                variant="secondary"
                                className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0 bg-background/95 hover:bg-background shadow-md"
                                onClick={(e) => e.stopPropagation()}
                              >
                                <MoreHorizontal className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem onClick={(e) => {
                                e.stopPropagation();
                                setEditingDirectory(directory.id);
                                setEditDirectoryName(directory.name);
                              }}>
                                <Edit className="h-4 w-4 mr-2" />
                                {t('Edit')}
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  handleDeleteDirectory(directory.id);
                                }}
                                className="text-destructive focus:text-destructive"
                              >
                                <Trash2 className="h-4 w-4 mr-2" />
                                {t('Delete')}
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                        <div className="absolute top-2 left-2">
                          <Badge variant="secondary" className="text-xs bg-primary/10 text-primary">
                            {t('FOLDER')}
                          </Badge>
                        </div>
                      </div>
                      <div className="p-3 space-y-2">
                        <div>
                          <h3 className="text-sm font-medium truncate flex items-center gap-2" title={directory.name}>
                            <FolderOpen className="h-4 w-4 text-primary" />
                            {directory.name}
                          </h3>
                          <p className="text-xs text-muted-foreground mt-1">
                            {t('Directory')}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}

                  {/* Media Files */}
                  {media.map((item) => (
                    <div
                      key={item.id}
                      className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200"
                    >
                      <div className="relative aspect-square bg-muted flex items-center justify-center">
                        <div className="absolute top-2 left-2 z-10">
                          <Checkbox
                            checked={selectedIds.includes(item.id)}
                            onCheckedChange={() => toggleSelected(item.id)}
                            onClick={(e) => e.stopPropagation()}
                            className="bg-background/90 border-2"
                          />
                        </div>
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
                          <div className="flex flex-col items-center justify-center p-4">
                            <div className="mb-2 text-2xl">
                              {getFileIcon(item.mime_type)}
                            </div>
                            <div className="text-xs text-center font-medium text-muted-foreground truncate w-full">
                              {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                            </div>
                          </div>
                        )}

                        <div className="absolute inset-0 bg-primary/0 group-hover:bg-primary/10 transition-all duration-200" />

                        {!infoModalOpen && !isUploadModalOpen && (
                          <div className="absolute top-2 right-2">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="secondary"
                                  className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0 bg-background/95 hover:bg-background shadow-md"
                                >
                                  <MoreHorizontal className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end" className="w-44">
                                <DropdownMenuItem onClick={() => handleShowInfo(item)}>
                                  <Info className="h-4 w-4 mr-2" />
                                  {t('View Info')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => openRenameDialog(item)}>
                                  <Edit className="h-4 w-4 mr-2" />
                                  {t('Rename')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => openMoveDialog(item)}>
                                  <FolderInput className="h-4 w-4 mr-2" />
                                  {t('Move to...')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleCopyLink(item.url)}>
                                  <Copy className="h-4 w-4 mr-2" />
                                  {t('Copy Link')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleDownload(item.id, item.file_name)}>
                                  <Download className="h-4 w-4 mr-2" />
                                  {t('Download')}
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                  onClick={() => deleteMedia(item.id)}
                                  className="text-destructive focus:text-destructive"
                                >
                                  <X className="h-4 w-4 mr-2" />
                                  {t('Delete')}
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        )}

                        <div className="absolute bottom-2 left-2">
                          <Badge variant="secondary" className="text-xs bg-background/95">
                            {item.mime_type.split('/')[1]?.toUpperCase()}
                          </Badge>
                        </div>
                      </div>

                      <div className="p-3 space-y-2">
                        <div>
                          <h3 className="text-sm font-medium truncate" title={item.name}>
                            {item.name}
                          </h3>
                          <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                            <HardDrive className="h-3 w-3" />
                            {formatFileSize(item.size)}
                          </p>
                        </div>

                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            {new Date(item.created_at).toLocaleDateString()}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Pagination */}
                {pagination.last_page > 1 && (
                  <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t mt-6">
                    <div className="text-sm text-muted-foreground">
                      {t('Page')} <span className="font-semibold">{pagination.current_page}</span> {t('of')} <span className="font-semibold">{pagination.last_page}</span> ({pagination.total} {t('files')})
                    </div>

                    <div className="flex items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        disabled={pagination.current_page === 1}
                        onClick={() => setPage(Math.max(pagination.current_page - 1, 1))}
                      >
                        {t('Previous')}
                      </Button>

                      <div className="flex gap-1">
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
                              className="w-10 h-8"
                              onClick={() => setPage(page)}
                            >
                              {page}
                            </Button>
                          );
                        })}
                      </div>

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
              </>
            )}
          </CardContent>
        </Card>

        {/* Upload Modal */}
        <Dialog open={isUploadModalOpen} onOpenChange={setIsUploadModalOpen}>
          <DialogContent className="max-w-lg" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Upload className="h-5 w-5" />
                {t('Upload Files')}
              </DialogTitle>
              <DialogDescription>
                {t('Upload new files to your media library')}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-6">
              <div
                className={`relative border-2 border-dashed rounded-xl p-12 text-center transition-all duration-200 ${
                  dragActive
                    ? 'border-primary bg-primary/10 scale-[1.02]'
                    : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
                }`}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
              >
                <div className={`transition-all duration-200 ${dragActive ? 'scale-110' : ''}`}>
                  <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <Upload className={`h-8 w-8 transition-colors ${dragActive ? 'text-primary' : 'text-gray-400'}`} />
                  </div>
                  <h3 className="text-lg font-medium mb-2">
                    {dragActive ? t('Drop files here') : t('Upload your files')}
                  </h3>
                  <p className="text-sm text-muted-foreground mb-6">
                    {t('Drag and drop your files here, or click to browse')}
                  </p>

                  <Input
                    type="file"
                    multiple
                    onChange={(e) => {
                      if (e.target.files) {
                        handleFileUpload(e.target.files);
                        setIsUploadModalOpen(false);
                      }
                    }}
                    className="hidden"
                    id="file-upload-modal"
                  />

                  <Button
                    type="button"
                    onClick={() => document.getElementById('file-upload-modal')?.click()}
                    disabled={uploading}
                    size="lg"
                  >
                    {uploading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        {t('Uploading...')}
                      </>
                    ) : (
                      <>
                        <Plus className="h-4 w-4 mr-2" />
                        {t('Choose Files')}
                      </>
                    )}
                  </Button>
                </div>

                {dragActive && <div className="absolute inset-0 bg-primary/10 rounded-xl" />}
              </div>
            </div>
          </DialogContent>
        </Dialog>

        {/* Rename Modal */}
        <Dialog open={!!renameTarget} onOpenChange={(open) => !open && setRenameTarget(null)}>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>{t('Rename File')}</DialogTitle>
            </DialogHeader>
            <Input
              value={renameValue}
              onChange={(e) => setRenameValue(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleRenameConfirm()}
              autoFocus
            />
            <DialogFooter>
              <Button variant="outline" onClick={() => setRenameTarget(null)}>{t('Cancel')}</Button>
              <Button onClick={handleRenameConfirm}>{t('Save')}</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Move To Folder Modal */}
        <Dialog open={!!moveTarget} onOpenChange={(open) => !open && setMoveTarget(null)}>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>{t('Move to Folder')}</DialogTitle>
              <DialogDescription>{moveTarget?.name}</DialogDescription>
            </DialogHeader>

            <div className="flex items-center flex-wrap gap-1 text-sm text-muted-foreground">
              <Button variant="ghost" size="sm" onClick={() => fetchMoveDirs(null)} className="h-7 px-2">
                <Home className="h-3.5 w-3.5 mr-1" />
                {t('Root')}
              </Button>
              {moveBreadcrumbs.map((crumb) => (
                <React.Fragment key={crumb.id}>
                  <span>/</span>
                  <Button variant="ghost" size="sm" onClick={() => fetchMoveDirs(crumb.id)} className="h-7 px-2">
                    {crumb.name}
                  </Button>
                </React.Fragment>
              ))}
            </div>

            <div className="border rounded-lg max-h-64 overflow-y-auto divide-y">
              {moveLoading ? (
                <div className="p-4 text-center text-sm text-muted-foreground">{t('Loading...')}</div>
              ) : moveDirs.length === 0 ? (
                <div className="p-4 text-center text-sm text-muted-foreground">{t('No subfolders here')}</div>
              ) : (
                moveDirs.map((dir) => (
                  <button
                    key={dir.id}
                    className="w-full flex items-center gap-2 p-3 text-sm hover:bg-muted/50 transition-colors text-left"
                    onClick={() => fetchMoveDirs(dir.id)}
                  >
                    <Folder className="h-4 w-4 text-primary flex-shrink-0" />
                    {dir.name}
                  </button>
                ))
              )}
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setMoveTarget(null)}>{t('Cancel')}</Button>
              <Button onClick={handleMoveHere}>
                {moveDirId === null ? t('Move to Root') : t('Move Here')}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Info Modal */}
        <Dialog open={infoModalOpen} onOpenChange={setInfoModalOpen}>
          <DialogContent className="max-w-lg" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Info className="h-5 w-5" />
                {t('File Information')}
              </DialogTitle>
              <DialogDescription>
                {t('View detailed information about this file')}
              </DialogDescription>
            </DialogHeader>

            {selectedMediaInfo && (
              <div className="space-y-6">
                <div className="flex justify-center bg-gray-50 rounded-lg p-4">
                  {selectedMediaInfo.mime_type.startsWith('image/') ? (
                    <img
                      src={selectedMediaInfo.thumb_url}
                      alt={selectedMediaInfo.name}
                      className="max-w-full h-48 object-contain rounded-md shadow-sm"
                      onError={(e) => {
                        e.currentTarget.src = selectedMediaInfo.url;
                      }}
                    />
                  ) : (
                    <div className="flex flex-col items-center justify-center h-48 w-full">
                      <div className="mb-4 text-6xl">
                        {getFileIcon(selectedMediaInfo.mime_type)}
                      </div>
                      <div className="text-sm font-medium text-muted-foreground">
                        {selectedMediaInfo.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                      </div>
                    </div>
                  )}
                </div>

                <div className="grid grid-cols-1 gap-4">
                  <div className="space-y-3">
                    <div className="flex justify-between items-start">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Name')}</span>
                      <span className="text-sm text-right max-w-xs truncate" title={selectedMediaInfo.file_name}>
                        {selectedMediaInfo.file_name}
                      </span>
                    </div>

                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Type')}</span>
                      <Badge variant="secondary">{selectedMediaInfo.mime_type}</Badge>
                    </div>

                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Size')}</span>
                      <span className="text-sm">{formatFileSize(selectedMediaInfo.size)}</span>
                    </div>

                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('Uploaded')}</span>
                      <span className="text-sm">{formatDate(selectedMediaInfo.created_at)}</span>
                    </div>
                  </div>

                  <div className="pt-2 border-t">
                    <span className="text-sm font-medium text-muted-foreground block mb-2">{t('URL')}</span>
                    <div className="flex items-center gap-2 p-2 bg-muted rounded-md">
                      <code className="text-xs text-muted-foreground flex-1 truncate">
                        {selectedMediaInfo.url}
                      </code>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleCopyLink(selectedMediaInfo.url)}
                        className="h-6 w-6 p-0"
                      >
                        <Copy className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                </div>

                <div className="flex gap-3 pt-2">
                  <Button
                    variant="outline"
                    onClick={() => handleCopyLink(selectedMediaInfo.url)}
                    className="flex-1"
                  >
                    <Copy className="h-4 w-4 mr-2" />
                    {t('Copy Link')}
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => handleDownload(selectedMediaInfo.id, selectedMediaInfo.file_name)}
                    className="flex-1"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download')}
                  </Button>
                </div>
              </div>
            )}
          </DialogContent>
        </Dialog>
      </div>
    </AuthenticatedLayout>
  );
}
