import * as React from "react"
import { Checkbox } from "@/components/ui/checkbox"
import { Label } from "@/components/ui/label"
import { cn } from "@/lib/utils"

interface CheckboxGroupProps {
  options: { value: string; label: string }[]
  value?: string[]
  onValueChange?: (value: string[]) => void
  className?: string
  disabled?: boolean
  direction?: 'horizontal' | 'vertical'
}

const CheckboxGroup = React.forwardRef<HTMLDivElement, CheckboxGroupProps>(
  ({ options, value = [], onValueChange, className, disabled, direction = 'horizontal', ...props }, ref) => {
    const handleCheckedChange = (optionValue: string, checked: boolean) => {
      if (!onValueChange) return
      
      if (checked) {
        onValueChange([...value, optionValue])
      } else {
        onValueChange(value.filter(v => v !== optionValue))
      }
    }

    return (
      <div ref={ref} className={cn(
        direction === 'horizontal' ? "flex flex-wrap gap-4" : "grid gap-3",
        className
      )} {...props}>
        {options.map((option) => (
          <div key={option.value} className="flex items-center space-x-2">
            <Checkbox
              id={option.value}
              checked={value.includes(option.value)}
              onCheckedChange={(checked) => handleCheckedChange(option.value, checked as boolean)}
              disabled={disabled}
            />
            <Label
              htmlFor={option.value}
              className="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
            >
              {option.label}
            </Label>
          </div>
        ))}
      </div>
    )
  }
)
CheckboxGroup.displayName = "CheckboxGroup"

export { CheckboxGroup }