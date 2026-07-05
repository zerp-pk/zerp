"use client"

import * as React from "react"
import { Check, ChevronDown, Search, X } from "lucide-react"
import { useTranslation } from 'react-i18next'
import { cn } from "@/lib/utils"
import { Button } from "./button"
import { Input } from "./input"
import { Popover, PopoverContent, PopoverTrigger } from "./popover"
import { Badge } from "./badge"

interface Option {
  value: string
  label: string
}

interface MultiSelectEnhancedProps {
  options: Option[]
  value: string[]
  onValueChange: (value: string[]) => void
  placeholder?: string
  searchable?: boolean
  className?: string
}

export function MultiSelectEnhanced({
  options,
  value,
  onValueChange,
  placeholder,
  searchable = false,
  className
}: MultiSelectEnhancedProps) {
  const { t } = useTranslation();
  const [open, setOpen] = React.useState(false)
  const [search, setSearch] = React.useState("")

  const filteredOptions = React.useMemo(() => {
    if (!searchable || !search) return options
    return options.filter(option =>
      option.label.toLowerCase().includes(search.toLowerCase())
    )
  }, [options, search, searchable])

  const selectedOptions = options.filter(option => value.includes(option.value))

  const handleSelect = (optionValue: string) => {
    const newValue = value.includes(optionValue)
      ? value.filter(v => v !== optionValue)
      : [...value, optionValue]
    onValueChange(newValue)
    setSearch("")
  }

  const handleRemove = (optionValue: string) => {
    onValueChange(value.filter(v => v !== optionValue))
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn("w-full justify-between", className)}
        >
          {value.length > 0 ? (
            <div className="flex flex-wrap gap-1 pointer-events-none">
              {selectedOptions.slice(0, 2).map(option => (
                <Badge key={option.value} variant="secondary" className="mr-1">
                  {option.label}
                  <div
                    className="ml-1 h-auto p-0 pointer-events-auto cursor-pointer hover:bg-gray-100 rounded"
                    onClick={(e) => {
                      e.preventDefault()
                      e.stopPropagation()
                      handleRemove(option.value)
                    }}
                  >
                    <X className="h-3 w-3" />
                  </div>
                </Badge>
              ))}
              {value.length > 2 && (
                <Badge variant="secondary">+{value.length - 2} {t('more')}</Badge>
              )}
            </div>
          ) : (
            <span className="text-muted-foreground">{placeholder || t('Select...')}</span>
          )}
          <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
        <div className="flex flex-col">
          {searchable && (
            <div className="flex items-center border-b px-3 py-2">
              <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
              <Input
                placeholder={t('Search...')}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="h-8 border-0 p-0 focus:border-primary"
              />
            </div>
          )}
          <div 
            className="max-h-[200px] overflow-y-auto"
            onWheel={(e) => {
              // Ensure wheel events are handled properly
              e.currentTarget.scrollTop += e.deltaY;
            }}
            tabIndex={-1}
          >
            {filteredOptions.length === 0 ? (
              <div className="py-6 text-center text-sm text-muted-foreground">
                {t('No options found.')}
              </div>
            ) : (
              filteredOptions.map((option) => {
                const isSelected = value.includes(option.value)
                
                return (
                  <div
                    key={option.value}
                    className={cn(
                      "relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground",
                      isSelected && "bg-accent text-accent-foreground"
                    )}
                    onClick={() => handleSelect(option.value)}
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4",
                        isSelected ? "opacity-100" : "opacity-0"
                      )}
                    />
                    {option.label}
                  </div>
                )
              })
            )}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  )
}