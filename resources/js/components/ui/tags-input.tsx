"use client"

import * as React from "react"
import { X } from "lucide-react"
import { Input } from "./input"
import { Badge } from "./badge"
import { cn } from "@/lib/utils"

export interface TagsInputProps {
  value: string[]
  onChange: (tags: string[]) => void
  placeholder?: string
  className?: string
  disabled?: boolean
  separator?: string
  options?: { value: string; label: string }[]
  allowCustom?: boolean
}

const TagsInput = React.forwardRef<HTMLDivElement, TagsInputProps>(
  ({ value = [], onChange, placeholder = "Type and press Enter...", className, disabled, separator = ",", options = [], allowCustom = true }, ref) => {
    const [inputValue, setInputValue] = React.useState("")
    const [showOptions, setShowOptions] = React.useState(false)
    const inputRef = React.useRef<HTMLInputElement>(null)
    const containerRef = React.useRef<HTMLDivElement>(null)

    const filteredOptions = React.useMemo(() => {
      if (!inputValue) return options
      return options.filter(option => 
        option.label.toLowerCase().includes(inputValue.toLowerCase()) &&
        !value.includes(option.value)
      )
    }, [options, inputValue, value])

    const addTag = (tag: string) => {
      const trimmedTag = tag.trim()
      if (trimmedTag && !value.includes(trimmedTag)) {
        onChange([...value, trimmedTag])
      }
      setInputValue("")
    }

    const removeTag = (tagToRemove: string) => {
      onChange(value.filter(tag => tag !== tagToRemove))
    }

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === "Enter" || e.key === separator) {
        e.preventDefault()
        if (filteredOptions.length > 0 && showOptions) {
          selectOption(filteredOptions[0])
        } else if (allowCustom) {
          addTag(inputValue)
        }
        setShowOptions(false)
      } else if (e.key === "Escape") {
        setShowOptions(false)
      } else if (e.key === "Backspace" && !inputValue && value.length > 0) {
        removeTag(value[value.length - 1])
      }
    }

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
      const newValue = e.target.value
      setInputValue(newValue)
      setShowOptions(newValue.length > 0 && options.length > 0)
      
      if (newValue.includes(separator)) {
        const tags = newValue.split(separator)
        tags.slice(0, -1).forEach(tag => addTag(tag))
        setInputValue(tags[tags.length - 1] || "")
      }
    }

    const selectOption = (option: { value: string; label: string }) => {
      addTag(option.value)
      setShowOptions(false)
    }

    React.useEffect(() => {
      const handleClickOutside = (event: MouseEvent) => {
        if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
          setShowOptions(false)
        }
      }
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }, [])

    return (
      <div ref={containerRef} className="relative">
        <div
          ref={ref}
          className={cn(
            "flex min-h-10 w-full flex-wrap gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2",
            disabled && "cursor-not-allowed opacity-50",
            className
          )}
          onClick={() => inputRef.current?.focus()}
        >
          {value.map((tag) => (
            <Badge key={tag} variant="secondary" className="gap-1">
              {tag}
              {!disabled && (
                <X
                  className="h-3 w-3 cursor-pointer hover:text-destructive"
                  onClick={(e) => {
                    e.stopPropagation()
                    removeTag(tag)
                  }}
                />
              )}
            </Badge>
          ))}
          <Input
            ref={inputRef}
            value={inputValue}
            onChange={handleInputChange}
            onKeyDown={handleKeyDown}
            onFocus={() => setShowOptions(inputValue.length > 0 && options.length > 0)}
            placeholder={value.length === 0 ? placeholder : ""}
            disabled={disabled}
            className="flex-1 border-0 p-0 shadow-none focus-visible:ring-0"
          />
        </div>
        
        {showOptions && filteredOptions.length > 0 && (
          <div className="absolute z-50 w-full mt-1 bg-background border border-input rounded-md shadow-lg max-h-48 overflow-y-auto">
            {filteredOptions.map((option) => (
              <div
                key={option.value}
                className="px-3 py-2 text-sm cursor-pointer hover:bg-accent hover:text-accent-foreground"
                onClick={() => selectOption(option)}
              >
                {option.label}
              </div>
            ))}
          </div>
        )}
      </div>
    )
  }
)

TagsInput.displayName = "TagsInput"

export { TagsInput }