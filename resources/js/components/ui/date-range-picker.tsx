"use client"

import * as React from "react"
import { Calendar as CalendarIcon } from "lucide-react"
import DatePicker from "react-datepicker"
import "react-datepicker/dist/react-datepicker.css"
import { useTranslation } from 'react-i18next'

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

interface DateRangePickerProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  id?: string
  required?: boolean
}

export function DateRangePicker({
  value,
  onChange,
  placeholder,
  className,
  id,
  required
}: DateRangePickerProps) {
  const { t } = useTranslation();
  const [open, setOpen] = React.useState(false)

  const parseValue = (val?: string): [Date | null, Date | null] => {
    if (!val) return [null, null]
    const [start, end] = val.split(' - ')
    return [
      start ? new Date(start) : null,
      end ? new Date(end) : null
    ]
  }

  const formatValue = (startDate: Date | null, endDate: Date | null) => {
    if (!startDate || !endDate) return ''
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }
    return `${startDate.toLocaleDateString('en-US', options)} - ${endDate.toLocaleDateString('en-US', options)}`
  }

  const [startDate, endDate] = parseValue(value)

  const handleChange = (dates: [Date | null, Date | null]) => {
    const [start, end] = dates
    if (start && end) {
      const startStr = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`
      const endStr = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`
      onChange(`${startStr} - ${endStr}`)
      setOpen(false)
    } else if (start && !end) {
      const startStr = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`
      onChange(`${startStr} - `)
    } else {
      onChange('')
    }
  }

  return (
    <div className={cn("w-full", className)}>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              'w-full justify-start text-left font-normal h-10',
              !value && 'text-muted-foreground'
            )}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {value && startDate && endDate ? formatValue(startDate, endDate) : (placeholder || t('Select date range'))}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <div className="date-range-wrapper">
            <DatePicker
              selected={startDate}
              onChange={handleChange}
              startDate={startDate}
              endDate={endDate}
              selectsRange
              monthsShown={2}
              inline
              showPopperArrow={false}
            />
          </div>
        </PopoverContent>
      </Popover>

      <style>{`
        .date-range-wrapper .react-datepicker {
          font-family: inherit;
          border: none;
          background: hsl(var(--background));
          color: hsl(var(--foreground));
        }
        .date-range-wrapper .react-datepicker__header {
          background: hsl(var(--background));
          border-bottom: 1px solid hsl(var(--border));
          border-radius: 0;
        }
        .date-range-wrapper .react-datepicker__current-month,
        .date-range-wrapper .react-datepicker__day-name {
          color: hsl(var(--foreground));
          font-weight: 500;
        }
        .date-range-wrapper .react-datepicker__day {
          color: hsl(var(--foreground));
          border-radius: 6px;
        }
        .date-range-wrapper .react-datepicker__day:hover {
          background: hsl(var(--accent));
          color: hsl(var(--accent-foreground));
        }
        .date-range-wrapper .react-datepicker__day--selected,
        .date-range-wrapper .react-datepicker__day--in-selecting-range,
        .date-range-wrapper .react-datepicker__day--in-range {
          background: hsl(var(--primary));
          color: hsl(var(--primary-foreground));
        }
        .date-range-wrapper .react-datepicker__day--range-start,
        .date-range-wrapper .react-datepicker__day--range-end {
          background: hsl(var(--primary));
          color: hsl(var(--primary-foreground));
        }
        .date-range-wrapper .react-datepicker__navigation {
          border: none;
          border-radius: 6px;
        }
        .date-range-wrapper .react-datepicker__navigation:hover {
          background: hsl(var(--accent));
        }
        .date-range-wrapper .react-datepicker__navigation-icon::before {
          border-color: hsl(var(--foreground));
        }
        .date-range-wrapper .react-datepicker__day--outside-month {
          color: hsl(var(--muted-foreground));
        }
        .date-range-wrapper .react-datepicker__day--disabled {
          color: hsl(var(--muted-foreground));
          opacity: 0.5;
        }
        .date-range-wrapper .react-datepicker__month-container {
          background: hsl(var(--background));
        }
      `}</style>
    </div>
  )
}