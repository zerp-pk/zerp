"use client"

import * as React from "react"
import { Clock } from "lucide-react"
import { useTranslation } from 'react-i18next'
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { ScrollArea } from "@/components/ui/scroll-area"

interface TimePickerProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  id?: string
  required?: boolean
  style?: React.CSSProperties
  disabled?: boolean
}

export function TimePicker({
  value,
  onChange,
  placeholder,
  className,
  id,
  required,
  style,
  disabled
}: TimePickerProps) {
  const { t } = useTranslation();
  const [open, setOpen] = React.useState(false)
  const [hour, setHour] = React.useState(value ? value.split(':')[0] : '09')
  const [minute, setMinute] = React.useState(value ? value.split(':')[1] : '00')
  const hourRef = React.useRef<HTMLDivElement>(null)
  const minuteRef = React.useRef<HTMLDivElement>(null)

  React.useEffect(() => {
    if (value) {
      const [h, m] = value.split(':')
      setHour(h)
      setMinute(m)
    }
  }, [value])

  React.useEffect(() => {
    if (open) {
      setTimeout(() => {
        const hourEl = hourRef.current?.querySelector(`[data-value="${hour}"]`) as HTMLElement
        const minuteEl = minuteRef.current?.querySelector(`[data-value="${minute}"]`) as HTMLElement
        if (hourEl && hourRef.current) {
          hourRef.current.scrollTop = hourEl.offsetTop - 80
        }
        if (minuteEl && minuteRef.current) {
          minuteRef.current.scrollTop = minuteEl.offsetTop - 80
        }
      }, 50)
    }
  }, [open])

  const handleHourClick = (h: string) => {
    setHour(h)
    const hourEl = hourRef.current?.querySelector(`[data-value="${h}"]`) as HTMLElement
    if (hourEl && hourRef.current) {
      hourRef.current.scrollTo({ top: hourEl.offsetTop - 80, behavior: 'smooth' })
    }
  }

  const handleMinuteClick = (m: string) => {
    setMinute(m)
    const minuteEl = minuteRef.current?.querySelector(`[data-value="${m}"]`) as HTMLElement
    if (minuteEl && minuteRef.current) {
      minuteRef.current.scrollTo({ top: minuteEl.offsetTop - 80, behavior: 'smooth' })
    }
  }

  const handleApply = () => {
    onChange(`${hour}:${minute}`)
    setOpen(false)
  }

  const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'))
  const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'))

  return (
    <div className={cn("w-full", className)}>
      {id && <input id={id} type="hidden" value={value || ''} required={required} />}
      <Popover open={open && !disabled} onOpenChange={disabled ? undefined : setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              'w-full justify-start text-left font-normal h-10',
              !value && 'text-muted-foreground',
              disabled && 'opacity-50 cursor-not-allowed'
            )}
            style={style}
            disabled={disabled}
          >
            <Clock className="mr-2 h-4 w-4" />
            {value || (placeholder || t('Select time'))}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <div className="flex gap-0">
            <div className="flex flex-col">
              <div className="text-xs font-semibold text-center py-3 border-b">{t('Hour')}</div>
              <div className="relative h-[200px] overflow-y-auto overflow-x-hidden" ref={hourRef}>
                <div className="absolute inset-x-0 top-[80px] h-[40px] bg-accent/50 pointer-events-none z-10" />
                {hours.map((h) => (
                  <button
                    key={h}
                    type="button"
                    data-value={h}
                    onClick={() => handleHourClick(h)}
                    className={cn(
                      "w-16 h-[40px] flex items-center justify-center text-sm font-medium hover:bg-accent transition-colors",
                      hour === h && "text-primary font-bold"
                    )}
                  >
                    {h}
                  </button>
                ))}
              </div>
            </div>
            <div className="border-l" />
            <div className="flex flex-col">
              <div className="text-xs font-semibold text-center py-3 border-b">{t('Minute')}</div>
              <div className="relative h-[200px] overflow-y-auto overflow-x-hidden" ref={minuteRef}>
                <div className="absolute inset-x-0 top-[80px] h-[40px] bg-accent/50 pointer-events-none z-10" />
                {minutes.map((m) => (
                  <button
                    key={m}
                    type="button"
                    data-value={m}
                    onClick={() => handleMinuteClick(m)}
                    className={cn(
                      "w-16 h-[40px] flex items-center justify-center text-sm font-medium hover:bg-accent transition-colors",
                      minute === m && "text-primary font-bold"
                    )}
                  >
                    {m}
                  </button>
                ))}
              </div>
            </div>
          </div>
          <div className="p-3 border-t">
            <Button onClick={handleApply} className="w-full" size="sm">
              {t('Apply')}
            </Button>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  )
}
