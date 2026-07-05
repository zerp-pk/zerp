import React, { useState, useEffect } from 'react';
import { Button } from './button';
import { ChevronLeft, ChevronRight, X, ZoomIn, Download } from 'lucide-react';
import { cn } from '@/lib/utils';
import { getImagePath } from '@/utils/helpers';

interface ImageSliderProps {
  images: string[];
  className?: string;
  showThumbnails?: boolean;
  showControls?: boolean;
  autoPlay?: boolean;
  autoPlayInterval?: number;
  onImageClick?: (index: number) => void;
  onClose?: () => void;
  showCloseButton?: boolean;
  aspectRatio?: 'square' | 'video' | 'auto';
  showZoom?: boolean;
  showDownload?: boolean;
}

export function ImageSlider({
  images,
  className,
  showThumbnails = true,
  showControls = true,
  autoPlay = false,
  autoPlayInterval = 3000,
  onImageClick,
  onClose,
  showCloseButton = false,
  aspectRatio = 'auto',
  showZoom = false,
  showDownload = false
}: ImageSliderProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isZoomed, setIsZoomed] = useState(false);

  const processedImages = images.map(img => img ? getImagePath(img) : '').filter(Boolean);

  useEffect(() => {
    if (!autoPlay || processedImages.length <= 1) return;
    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % processedImages.length);
    }, autoPlayInterval);
    return () => clearInterval(interval);
  }, [autoPlay, autoPlayInterval, processedImages.length]);

  if (!processedImages.length) {
    return (
      <div className={cn("flex items-center justify-center bg-muted rounded-lg h-64 w-full", className)}>
        <p className="text-muted-foreground text-sm">No images available</p>
      </div>
    );
  }

  const aspectRatioClass = {
    square: 'aspect-square',
    video: 'aspect-video', 
    auto: 'min-h-[200px]'
  }[aspectRatio];

  return (
    <div className={cn("relative w-full max-w-full", className)}>
      {/* Action Buttons */}
      {(showZoom || showDownload || showCloseButton) && (
        <div className="absolute top-3 right-3 z-20 flex gap-1">
          {showZoom && (
            <Button variant="outline" size="icon" className="h-8 w-8 bg-white/90 hover:bg-white shadow-sm" onClick={() => setIsZoomed(!isZoomed)}>
              <ZoomIn className="h-3 w-3" />
            </Button>
          )}
          {showDownload && (
            <Button variant="outline" size="icon" className="h-8 w-8 bg-white/90 hover:bg-white shadow-sm" onClick={() => {
              const link = document.createElement('a');
              link.href = processedImages[currentIndex];
              link.download = `image-${currentIndex + 1}.jpg`;
              link.click();
            }}>
              <Download className="h-3 w-3" />
            </Button>
          )}
          {showCloseButton && onClose && (
            <Button variant="outline" size="icon" className="h-8 w-8 bg-white/90 hover:bg-white shadow-sm" onClick={onClose}>
              <X className="h-3 w-3" />
            </Button>
          )}
        </div>
      )}

      {/* Main Image Container */}
      <div className={cn("relative overflow-hidden rounded-lg bg-muted border", aspectRatioClass)}>
        <img
          src={processedImages[currentIndex]}
          alt={`Image ${currentIndex + 1}`}
          className={cn(
            "w-full h-full object-cover cursor-pointer transition-transform duration-300",
            isZoomed ? "scale-150" : "hover:scale-[1.02]"
          )}
          onClick={() => onImageClick?.(currentIndex)}
        />

        {/* Navigation Controls */}
        {showControls && processedImages.length > 1 && (
          <>
            <Button
              variant="outline"
              size="icon"
              className="absolute left-3 top-1/2 -translate-y-1/2 h-8 w-8 bg-white/90 hover:bg-white shadow-sm"
              onClick={() => setCurrentIndex((prev) => (prev - 1 + processedImages.length) % processedImages.length)}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              className="absolute right-3 top-1/2 -translate-y-1/2 h-8 w-8 bg-white/90 hover:bg-white shadow-sm"
              onClick={() => setCurrentIndex((prev) => (prev + 1) % processedImages.length)}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </>
        )}

        {/* Slide Indicators */}
        {processedImages.length > 1 && (
          <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            {processedImages.map((_, index) => (
              <button
                key={index}
                className={cn(
                  "w-2 h-2 rounded-full transition-all duration-200",
                  index === currentIndex ? "bg-white scale-125" : "bg-white/60 hover:bg-white/80"
                )}
                onClick={() => setCurrentIndex(index)}
              />
            ))}
          </div>
        )}

        {/* Image Counter */}
        {processedImages.length > 1 && (
          <div className="absolute top-3 left-3 bg-black/60 text-white rounded px-2 py-1 text-xs font-medium">
            {currentIndex + 1} / {processedImages.length}
          </div>
        )}
      </div>

      {/* Thumbnails */}
      {showThumbnails && processedImages.length > 1 && (
        <div className="flex gap-2 mt-3 overflow-x-auto pb-2 scrollbar-hide">
          {processedImages.map((image, index) => (
            <button
              key={index}
              className={cn(
                "flex-shrink-0 w-16 h-16 rounded-md overflow-hidden border-2 transition-all duration-200",
                index === currentIndex ? "border-primary ring-2 ring-primary/20" : "border-border hover:border-primary/50"
              )}
              onClick={() => setCurrentIndex(index)}
            >
              <img src={image} alt={`Thumbnail ${index + 1}`} className="w-full h-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}