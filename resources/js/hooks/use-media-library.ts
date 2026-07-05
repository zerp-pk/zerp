import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import type { Breadcrumb, MediaDirectory, MediaItem, MediaPaginationMeta } from '@/types/media';

interface UseMediaLibraryOptions {
  perPage?: number;
  /** Skip the initial/reactive fetch until this becomes true (e.g. a modal that isn't open yet). */
  enabled?: boolean;
}

function csrfToken(): string {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function useMediaLibrary(options: UseMediaLibraryOptions = {}) {
  const { perPage = 24, enabled = true } = options;
  const { t } = useTranslation();

  const [media, setMedia] = useState<MediaItem[]>([]);
  const [directories, setDirectories] = useState<MediaDirectory[]>([]);
  const [breadcrumbs, setBreadcrumbs] = useState<Breadcrumb[]>([]);
  const [currentDirectoryId, setCurrentDirectoryId] = useState<number | null>(null);
  const [pagination, setPagination] = useState<MediaPaginationMeta>({ current_page: 1, per_page: perPage, total: 0, last_page: 1 });
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');

  const abortRef = useRef<AbortController | null>(null);

  // Debounce search input -> actual query term.
  useEffect(() => {
    const handle = setTimeout(() => setDebouncedSearchTerm(searchTerm.trim()), 300);
    return () => clearTimeout(handle);
  }, [searchTerm]);

  // Reset to page 1 whenever the folder or search term changes.
  useEffect(() => {
    setPage(1);
  }, [currentDirectoryId, debouncedSearchTerm]);

  const fetchMedia = useCallback(async () => {
    if (!enabled) return;

    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (currentDirectoryId) params.append('directory_id', String(currentDirectoryId));
      if (debouncedSearchTerm) params.append('q', debouncedSearchTerm);
      params.append('page', String(page));
      params.append('per_page', String(perPage));

      const response = await fetch(`${route('media.index')}?${params}`, {
        credentials: 'same-origin',
        signal: controller.signal,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();
      setMedia(data.media?.data ?? []);
      setPagination({
        current_page: data.media?.current_page ?? 1,
        per_page: data.media?.per_page ?? perPage,
        total: data.media?.total ?? 0,
        last_page: data.media?.last_page ?? 1,
      });
      setDirectories(data.directories ?? []);
      setBreadcrumbs(data.breadcrumbs ?? []);
    } catch (error) {
      if ((error as Error).name !== 'AbortError') {
        toast.error(t('Failed to load media'));
      }
    } finally {
      setLoading(false);
    }
  }, [enabled, currentDirectoryId, debouncedSearchTerm, page, perPage, t]);

  useEffect(() => {
    fetchMedia();
    return () => abortRef.current?.abort();
  }, [fetchMedia]);

  const navigateToDirectory = useCallback((id: number | null) => {
    setCurrentDirectoryId(id);
  }, []);

  const goToBreadcrumb = useCallback((id: number | null) => {
    setCurrentDirectoryId(id);
  }, []);

  const createDirectory = useCallback(async (name: string, parentId?: number | null) => {
    if (!name.trim()) return false;
    try {
      const response = await fetch(route('media.directories.create'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ name, parent_id: parentId ?? null }),
      });
      const result = await response.json();
      if (response.ok) {
        toast.success(result.message || t('Directory created successfully'));
        await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to create directory'));
      return false;
    } catch (error) {
      toast.error(t('Failed to create directory'));
      return false;
    }
  }, [fetchMedia, t]);

  const renameDirectory = useCallback(async (id: number, name: string) => {
    if (!name.trim()) return false;
    try {
      const response = await fetch(route('media.directories.update', id), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ name }),
      });
      const result = await response.json();
      if (response.ok) {
        toast.success(result.message || t('Directory updated successfully'));
        await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to update directory'));
      return false;
    } catch (error) {
      toast.error(t('Failed to update directory'));
      return false;
    }
  }, [fetchMedia, t]);

  const deleteDirectory = useCallback(async (id: number) => {
    try {
      const response = await fetch(route('media.directories.destroy', id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
      });
      const result = await response.json().catch(() => ({}));
      if (response.ok) {
        toast.success(result.message || t('Directory deleted successfully'));
        if (currentDirectoryId === id) setCurrentDirectoryId(null);
        else await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to delete directory'));
      return false;
    } catch (error) {
      toast.error(t('Failed to delete directory'));
      return false;
    }
  }, [currentDirectoryId, fetchMedia, t]);

  const uploadFiles = useCallback(async (files: FileList | File[]) => {
    const fileArray = Array.from(files);
    if (fileArray.length === 0) return;

    setUploading(true);
    const formData = new FormData();
    fileArray.forEach((file) => formData.append('files[]', file));
    if (currentDirectoryId) formData.append('directory_id', String(currentDirectoryId));

    try {
      const response = await fetch(route('media.batch'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
        credentials: 'same-origin',
      });
      const result = await response.json();

      if (response.ok) {
        await fetchMedia();
        if (result.errors && result.errors.length > 0) {
          toast.warning(result.message);
          result.errors.forEach((error: string) => toast.error(error, { duration: 5000 }));
        } else {
          toast.success(result.message || t('file(s) uploaded successfully'));
        }
      } else {
        toast.error(result.message || t('Upload failed'));
        (result.errors || []).forEach((error: string) => toast.error(error, { duration: 5000 }));
      }
    } catch (error) {
      toast.error(t('Upload failed'));
    } finally {
      setUploading(false);
    }
  }, [currentDirectoryId, fetchMedia, t]);

  const deleteMedia = useCallback(async (id: number) => {
    try {
      const response = await fetch(route('media.destroy', id), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      if (response.ok) {
        toast.success(t('Media deleted successfully'));
        await fetchMedia();
        return true;
      }
      toast.error(t('Failed to delete media'));
      return false;
    } catch (error) {
      toast.error(t('Error deleting media'));
      return false;
    }
  }, [fetchMedia, t]);

  const bulkDeleteMedia = useCallback(async (ids: number[]) => {
    if (ids.length === 0) return false;
    try {
      const response = await fetch(route('media.bulk-destroy'), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ ids }),
      });
      const result = await response.json();
      if (response.ok) {
        toast.success(result.message || t('Files deleted'));
        await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to delete files'));
      return false;
    } catch (error) {
      toast.error(t('Failed to delete files'));
      return false;
    }
  }, [fetchMedia, t]);

  const renameMedia = useCallback(async (id: number, name: string) => {
    if (!name.trim()) return false;
    try {
      const response = await fetch(route('media.rename', id), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ name }),
      });
      const result = await response.json();
      if (response.ok) {
        toast.success(result.message || t('The file has been renamed.'));
        await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to rename file'));
      return false;
    } catch (error) {
      toast.error(t('Failed to rename file'));
      return false;
    }
  }, [fetchMedia, t]);

  const moveMedia = useCallback(async (id: number, directoryId: number | null) => {
    try {
      const response = await fetch(route('media.directory.update', id), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ directory_id: directoryId }),
      });
      const result = await response.json();
      if (response.ok) {
        toast.success(result.message || t('The media moved successfully.'));
        await fetchMedia();
        return true;
      }
      toast.error(result.message || t('Failed to move file'));
      return false;
    } catch (error) {
      toast.error(t('Failed to move file'));
      return false;
    }
  }, [fetchMedia, t]);

  return {
    media,
    directories,
    breadcrumbs,
    currentDirectoryId,
    pagination,
    loading,
    uploading,
    searchTerm,
    setSearchTerm,
    fetchMedia,
    refresh: fetchMedia,
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
  };
}
