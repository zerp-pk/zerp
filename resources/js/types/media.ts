export interface MediaItem {
  id: number;
  name: string;
  file_name: string;
  url: string;
  thumb_url: string;
  size: number;
  mime_type: string;
  directory_id: number | null;
  created_at: string;
}

export interface MediaDirectory {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
}

export interface Breadcrumb {
  id: number;
  name: string;
}

export interface MediaPaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface MediaIndexResponse {
  media: MediaPaginationMeta & { data: MediaItem[] };
  directories: MediaDirectory[];
  breadcrumbs: Breadcrumb[];
  current_directory: { id: number; name: string } | null;
}
