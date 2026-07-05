import { ChevronLeft, ChevronRight, MoreHorizontal } from "lucide-react";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';

interface PaginationProps {
  data: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  routeName?: string;
  filters?: Record<string, any>;
  onPageChange?: (page: number) => void;
}

export function Pagination({ data, routeName, filters = {}, onPageChange }: PaginationProps) {
  const { t } = useTranslation();
  const { current_page, last_page, per_page, total, from, to } = data;

  const goToPage = (page: number) => {
    if (onPageChange) {
      onPageChange(page);
    } else if (routeName) {
      router.get(route(routeName), { ...filters, page, per_page }, {
        preserveState: true,
        replace: true
      });
    }
  };

  const renderPageNumbers = () => {
    const pages = [];
    const showPages = 5;
    let startPage = Math.max(1, current_page - Math.floor(showPages / 2));
    let endPage = Math.min(last_page, startPage + showPages - 1);

    if (endPage - startPage + 1 < showPages) {
      startPage = Math.max(1, endPage - showPages + 1);
    }

    if (startPage > 1) {
      pages.push(
        <Button key={1} variant="outline" size="sm" onClick={() => goToPage(1)}>
          1
        </Button>
      );
      if (startPage > 2) {
        pages.push(<MoreHorizontal key="start-ellipsis" className="h-4 w-4" />);
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(
        <Button
          key={i}
          variant={i === current_page ? "default" : "outline"}
          size="sm"
          onClick={() => goToPage(i)}
        >
          {i}
        </Button>
      );
    }

    if (endPage < last_page) {
      if (endPage < last_page - 1) {
        pages.push(<MoreHorizontal key="end-ellipsis" className="h-4 w-4" />);
      }
      pages.push(
        <Button key={last_page} variant="outline" size="sm" onClick={() => goToPage(last_page)}>
          {last_page}
        </Button>
      );
    }

    return pages;
  };

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-2 py-4">
      <div className="text-xs sm:text-sm text-muted-foreground">
        {t('Showing')} {from} {t('to')} {to} {t('of')} {total} {t('results')}
      </div>
      <div className="flex items-center gap-1 sm:space-x-2 flex-wrap justify-center">
        <Button
          variant="outline"
          size="sm"
          onClick={() => goToPage(current_page - 1)}
          disabled={current_page === 1}
          className="h-8"
        >
          <ChevronLeft className="h-4 w-4" />
          <span className="hidden sm:inline">{t('Previous')}</span>
        </Button>
        {renderPageNumbers()}
        <Button
          variant="outline"
          size="sm"
          onClick={() => goToPage(current_page + 1)}
          disabled={current_page === last_page}
          className="h-8"
        >
          <span className="hidden sm:inline">{t('Next')}</span>
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}