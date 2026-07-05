"use client"

import * as React from "react"
import { Calendar as CalendarIcon } from "lucide-react"
import ReactDatePicker from "react-datepicker"
import "react-datepicker/dist/react-datepicker.css"
import { useTranslation } from 'react-i18next'

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

interface YearPickerProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  academicYear?: boolean
  allowBoth?: boolean
}

export function YearPicker({
  value,
  onChange,
  placeholder,
  className,
  academicYear = false,
  allowBoth = false
}: YearPickerProps) {
  const { t } = useTranslation();
  const [open, setOpen] = React.useState(false)

  const handleChange = (date: Date | null) => {
    if (date) {
      const year = date.getFullYear();
      if (allowBoth) {
        // Show options for both formats
        const academicFormat = `${year}-${(year + 1).toString().slice(-2)}`;
        const singleYear = year.toString();
        // For now, default to academic year format when allowBoth is true
        onChange(academicFormat);
      } else {
        const yearValue = academicYear ? `${year}-${(year + 1).toString().slice(-2)}` : year.toString();
        onChange(yearValue);
      }
      setOpen(false);
    }
  }

  const getSelectedDate = () => {
    if (!value) return null;
    const year = value.includes('-') ? parseInt(value.split('-')[0]) : parseInt(value);
    return new Date(year, 0, 1);
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          className={cn(
            'w-full justify-start text-left font-normal h-10',
            !value && 'text-muted-foreground',
            className
          )}
        >
          <CalendarIcon className="mr-2 h-4 w-4" />
          {value || placeholder || t('Select Year')}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <div className="year-picker-wrapper">
          <ReactDatePicker
            selected={getSelectedDate()}
            onChange={handleChange}
            showYearPicker
            dateFormat="yyyy"
            yearItemNumber={12}
            minDate={new Date(new Date().getFullYear() - 50, 0, 1)}
            maxDate={new Date(new Date().getFullYear() + 50, 0, 1)}
            inline
          />
        </div>
        <style>{`
          .year-picker-wrapper .react-datepicker__year-wrapper {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 8px !important;
            padding: 16px !important;
            width: 200px !important;
          }
          .year-picker-wrapper .react-datepicker__year-text {
            width: 100% !important;
            margin: 0 !important;
            padding: 8px 4px !important;
            text-align: center !important;
            border-radius: 6px !important;
            font-size: 14px !important;
          }
          .year-picker-wrapper .react-datepicker__year-text:hover {
            background: hsl(var(--accent)) !important;
          }
          .year-picker-wrapper .react-datepicker__year-text--selected {
            background: hsl(var(--primary)) !important;
            color: hsl(var(--primary-foreground)) !important;
          }
          .year-picker-wrapper .react-datepicker__year-text--selected:hover {
            background: hsl(var(--primary)) !important;
            color: hsl(var(--primary-foreground)) !important;
          }
          .year-picker-wrapper .react-datepicker__year-text--today {
            background: hsl(var(--primary)) !important;
            color: hsl(var(--primary-foreground)) !important;
            font-weight: 600 !important;
          }
          .year-picker-wrapper .react-datepicker__year-text--today:hover {
            background: hsl(var(--primary)) !important;
            color: hsl(var(--primary-foreground)) !important;
          }
        `}</style>
      </PopoverContent>
    </Popover>
  )
}