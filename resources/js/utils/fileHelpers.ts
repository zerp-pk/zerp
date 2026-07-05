import { getImagePath } from './helpers';

/**
 * Get file URL from filename
 * Since we store only filenames using basename(), we need to construct the full path
 */
export const getFileUrl = (filename: string): string => {
  if (!filename) return '';
  
  // If it's already a full URL, return as is
  if (filename.startsWith('http')) return filename;
  
  // If it starts with /, it's already a path
  if (filename.startsWith('/')) return getImagePath(filename);
  
  // Otherwise, it's just a filename, so we need to construct the path
  return getImagePath(filename);
};

/**
 * Check if file is an image based on extension
 */
export const isImageFile = (filename: string): boolean => {
  if (!filename) return false;
  return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(filename);
};

/**
 * Get file extension from filename
 */
export const getFileExtension = (filename: string): string => {
  if (!filename) return '';
  const parts = filename.split('.');
  return parts.length > 1 ? parts.pop()?.toLowerCase() || '' : '';
};