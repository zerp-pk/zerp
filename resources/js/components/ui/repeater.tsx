"use client"

import * as React from "react"
import { Plus, Trash2 } from "lucide-react"
import { Button } from "./button"
import { Input } from "./input"
import { Textarea } from "./textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./select"
import { Checkbox } from "./checkbox"
import { Switch } from "./switch"
import { RadioGroup, RadioGroupItem } from "./radio-group"
import { DatePicker } from "./date-picker"
import { DateRangePicker } from "./date-range-picker"
import { DateTimeRangePicker } from "./datetime-range-picker"
import { TimePicker } from "./time-picker"
import { CurrencyInput } from "./currency-input"
import { PhoneInputComponent } from "./phone-input"
import { RichTextEditor } from "./rich-text-editor"
import { Slider } from "./slider"
import { Rating } from "./rating"
import { Toggle } from "./toggle"
import { MultiSelectEnhanced } from "./multi-select-enhanced"
import { CheckboxGroup } from "./checkbox-group"
import { SearchInput } from "./search-input"
import { TagsInput } from "./tags-input"
import { IconPicker } from "./icon-picker"
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "./tooltip"
import MediaPicker from "../MediaPicker"
import { SimpleMultiSelect } from "../simple-multi-select"
import { cn } from "@/lib/utils"

export interface RepeaterFieldLayout {
  colSpan?: number | { sm?: number; md?: number; lg?: number; xl?: number }
  order?: number
  width?: string
  hidden?: boolean
  className?: string
}

export interface RepeaterField {
  name: string
  label: string
  type: 'text' | 'email' | 'number' | 'password' | 'textarea' | 'select' | 'checkbox' | 'switch' | 'radio' | 'date' | 'daterange' | 'datetimerange' | 'timepicker' | 'image' | 'currency' | 'phone' | 'richtext' | 'slider' | 'rating' | 'toggle' | 'multiselect' | 'checkboxgroup' | 'search' | 'simplemultiselect' | 'color' | 'file' | 'url' | 'time' | 'datetime' | 'month' | 'week' | 'tags' | 'conditional' | 'repeater'| 'media' | 'icon'
  placeholder?: string
  options?: { value: string; label: string }[]
  required?: boolean
  disabled?: boolean
  className?: string
  min?: number
  max?: number
  step?: number
  rows?: number
  currency?: string
  dependsOn?: string
  conditions?: Record<string, Omit<RepeaterField, 'name' | 'label' | 'dependsOn' | 'conditions'>>
  layout?: RepeaterFieldLayout
}

export interface RepeaterItem {
  id: string
  [key: string]: any
}

export interface RepeaterLayoutConfig {
  type: 'grid' | 'flex' | 'stack' | 'custom'
  columns?: number | 'auto' | { sm?: number; md?: number; lg?: number; xl?: number }
  gap?: string
  className?: string
}

export interface RepeaterProps {
  fields: RepeaterField[]
  value: RepeaterItem[] | any
  onChange: (items: RepeaterItem[]) => void
  addButtonText?: string
  deleteTooltipText?: string
  className?: string
  minItems?: number
  maxItems?: number
  errors?: Record<string, Record<string, string>>
  renderCustomField?: (item: RepeaterItem, index: number, updateItem: (fieldName: string, value: any) => void) => React.ReactNode
  onValidationChange?: (isValid: boolean, errors: Record<string, Record<string, string>>) => void
  showDefault?: boolean
  layout?: RepeaterLayoutConfig
}

export interface RepeaterRef {
  validate: () => boolean
  getValidationErrors: () => Record<string, Record<string, string>>
}

const Repeater = React.forwardRef<RepeaterRef, RepeaterProps>(
  ({ 
    fields, 
    value = [], 
    onChange, 
    addButtonText = "Add Item",
    deleteTooltipText = "Delete",
    className,
    minItems = 0,
    maxItems,
    errors = {},
    renderCustomField,
    onValidationChange,
    showDefault = false,
    layout
  }, ref) => {
    const [validationErrors, setValidationErrors] = React.useState<Record<string, Record<string, string>>>({})
    const [showValidation, setShowValidation] = React.useState(false)
    const generateId = () => Math.random().toString(36).substring(2, 9)

    // Initialize with default item if showDefault is true and no items exist
    React.useEffect(() => {
      if (showDefault && (!value || value.length === 0)) {
        const defaultItem: RepeaterItem = {
          id: generateId(),
          ...fields.reduce((acc, field) => {
            if (field.type === 'checkbox' || field.type === 'switch') {
              acc[field.name] = false
            } else if (field.type === 'conditional' && field.conditions) {
              const firstConditionKey = Object.keys(field.conditions)[0]
              const firstCondition = field.conditions[firstConditionKey]
              acc[field.name] = firstCondition.type === 'checkbox' || firstCondition.type === 'switch' ? false : ''
            } else {
              acc[field.name] = ''
            }
            return acc
          }, {} as Record<string, any>)
        }
        onChange([defaultItem])
      }
    }, [showDefault, fields, onChange])

    const addItem = () => {
      if (maxItems && value.length >= maxItems) return
      
      const newItem: RepeaterItem = {
        id: generateId(),
        ...fields.reduce((acc, field, fieldIndex) => {
          if (field.type === 'checkbox' || field.type === 'switch') {
            acc[field.name] = false
          } else if (field.type === 'conditional' && field.conditions) {
            // Handle conditional fields - set default based on first condition
            const firstConditionKey = Object.keys(field.conditions)[0]
            const firstCondition = field.conditions[firstConditionKey]
            acc[field.name] = firstCondition.type === 'checkbox' || firstCondition.type === 'switch' ? false : ''
          } else {
            acc[field.name] = ''
          }
          return acc
        }, {} as Record<string, any>)
      }
      onChange([...value, newItem])
    }

    const removeItem = (id: string) => {
      const effectiveMinItems = showDefault ? Math.max(1, minItems) : minItems
      if (value.length <= effectiveMinItems) return
      onChange(value.filter((item: any) => item.id !== id))
    }

    const validateItems = React.useCallback(() => {
      const newErrors: Record<string, Record<string, string>> = {}
      let isValid = true

      value.forEach((item: RepeaterItem) => {
        fields.forEach((field) => {
          if (field.required) {
            const fieldValue = item[field.name]
            const isEmpty = fieldValue === '' || fieldValue === null || fieldValue === undefined || 
                           (Array.isArray(fieldValue) && fieldValue.length === 0)
            
            if (isEmpty) {
              if (!newErrors[item.id]) newErrors[item.id] = {}
              newErrors[item.id][field.name] = `${field.label} is required`
              isValid = false
            }
          }
        })
      })

      setValidationErrors(newErrors)
      onValidationChange?.(isValid, newErrors)
      return isValid
    }, [value, fields, onValidationChange])



    React.useImperativeHandle(ref, () => ({
      validate: validateItems,
      getValidationErrors: () => validationErrors
    }), [validateItems, validationErrors])

    // Intercept button clicks to prevent submission when validation fails
    React.useEffect(() => {
      const handleButtonClick = (e: Event) => {
        const target = e.target as HTMLElement
        const button = target.closest('button')
        
        if (button && (button.textContent?.includes('Save') || button.textContent?.includes('Change')  || button.textContent?.includes('Update') || button.textContent?.includes('Create') || button.textContent?.includes('Submit'))) {
          const isValid = validateItems()
          if (!isValid) {
            e.preventDefault()
            e.stopPropagation()
            e.stopImmediatePropagation()
            setShowValidation(true)
          }
        }
      }
      
      document.addEventListener('click', handleButtonClick, true)
      
      return () => {
        document.removeEventListener('click', handleButtonClick, true)
      }
    }, [validateItems])

    const updateItem = (id: string, fieldName: string, fieldValue: any) => {
      const updatedItems = value.map((item: any) => 
        item.id === id ? { ...item, [fieldName]: fieldValue } : item
      )
      onChange(updatedItems)
    }

    const getLayoutClassName = () => {
      if (!layout || layout.type === 'grid') {
        if (layout?.columns) {
          if (typeof layout.columns === 'object') {
            const { sm = 1, md = 2, lg = 2, xl = 3 } = layout.columns
            return `grid gap-${layout.gap || '4'} grid-cols-${sm} md:grid-cols-${md} lg:grid-cols-${lg} xl:grid-cols-${xl}`
          }
          if (layout.columns === 'auto') {
            return `grid gap-${layout.gap || '4'} grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`
          }
          return `grid gap-${layout.gap || '4'} grid-cols-${layout.columns}`
        }
        // Default grid behavior
        return `grid gap-4 ${fields.length === 1 ? 'grid-cols-1' : fields.length === 2 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 lg:grid-cols-2'}`
      }
      
      if (layout.type === 'flex') {
        return `flex flex-wrap gap-${layout.gap || '4'}`
      }
      
      if (layout.type === 'stack') {
        return `space-y-${layout.gap || '4'}`
      }
      
      if (layout.type === 'custom' && layout.className) {
        return layout.className
      }
      
      return `grid gap-4 ${fields.length === 1 ? 'grid-cols-1' : fields.length === 2 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 lg:grid-cols-2'}`
    }

    const renderField = (field: RepeaterField, item: RepeaterItem) => {
      const fieldValue = item[field.name]

      // Handle conditional fields
      if (field.type === 'conditional' && field.dependsOn && field.conditions) {
        const dependentValue = item[field.dependsOn]
        const condition = field.conditions[dependentValue]
        if (condition) {
          const conditionalField = { ...field, ...condition, type: condition.type }
          return renderField(conditionalField, item)
        }
        return null
      }

      switch (field.type) {
        case 'textarea':
          return (
            <Textarea
              placeholder={field.placeholder}
              value={fieldValue || ''}
              onChange={(e) => updateItem(item.id, field.name, e.target.value)}
              className={field.className}
              rows={field.rows || 3}
            />
          )

        case 'select':
          return (
            <Select 
              value={fieldValue || ''} 
              onValueChange={(value) => updateItem(item.id, field.name, value)}
            >
              <SelectTrigger className={field.className}>
                <SelectValue placeholder={field.placeholder} />
              </SelectTrigger>
              <SelectContent searchable={(field.options?.length || 0) > 10}>
                {field.options?.map((option, optionIndex) => (
                  <SelectItem key={`${item.id}-${field.name}-${option.value}-${optionIndex}`} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )

        case 'checkbox':
          return (
            <div key={`checkbox-wrapper-${item.id}-${field.name}`} className="flex items-center space-x-3 py-2">
              <Checkbox
                key={`checkbox-${item.id}-${field.name}`}
                id={`checkbox-${item.id}-${field.name}`}
                checked={fieldValue === true}
                onCheckedChange={(checked) => updateItem(item.id, field.name, checked)}
                className={cn("data-[state=checked]:bg-primary data-[state=checked]:border-primary", field.className)}
              />
              <label 
                htmlFor={`checkbox-${item.id}-${field.name}`}
                className="text-sm font-medium cursor-pointer select-none text-gray-700"
              >
                {field.label}
              </label>
            </div>
          )

        case 'switch':
          const switchId = `switch-${item.id}-${field.name}`;
          return (
            <div className="flex items-center space-x-2">
              <Switch
                id={switchId}
                checked={fieldValue || false}
                onCheckedChange={(checked) => updateItem(item.id, field.name, checked)}
                className={field.className}
              />
              <label htmlFor={switchId} className="text-sm font-medium cursor-pointer">{field.label}</label>
            </div>
          )

        case 'radio':
          return (
            <RadioGroup
              value={fieldValue || ''}
              onValueChange={(value) => updateItem(item.id, field.name, value)}
              className={field.className}
            >
              {field.options?.map((option, index) => {
                const radioId = `radio-${item.id}-${field.name}-${index}`;
                return (
                  <div key={`${item.id}-${field.name}-${option.value}-${index}`} className="flex items-center space-x-2">
                    <RadioGroupItem id={radioId} value={option.value} />
                    <label htmlFor={radioId} className="text-sm font-medium cursor-pointer">{option.label}</label>
                  </div>
                );
              })}
            </RadioGroup>
          )

        case 'date':
          return (
            <DatePicker
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'daterange':
          return (
            <DateRangePicker
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'datetimerange':
          return (
            <DateTimeRangePicker
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'timepicker':
          return (
            <TimePicker
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'repeater':
          return (
            <Repeater
              value={fieldValue || []}
              onChange={(value) => updateItem(item.id, field.name, value)}
              fields={(field.options && field.options.length > 0) ? field.options.map(opt => ({ type: 'text', name: opt.value, label: opt.label })) : [{ type: 'text', name: 'value', label: 'Value' }]}
              addButtonText="Add Item"
              className={field.className}
            />
          )

        case 'image':
        case 'media':
          return (
            <MediaPicker
              key={`${item.id}-${field.name}`}
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              multiple={false}
            />
          )

        case 'currency':
          return (
            <CurrencyInput
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              currency={field.currency}
              className={field.className}
              disabled={field.disabled}
            />
          )

        case 'phone':
          return (
            <PhoneInputComponent
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'richtext':
          return (
            <RichTextEditor
              content={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'slider':
          return (
            <div className="space-y-2">
              <Slider
                value={[fieldValue || field.min || 0]}
                onValueChange={(value) => updateItem(item.id, field.name, value[0])}
                min={field.min || 0}
                max={field.max || 100}
                step={field.step || 1}
                className={field.className}
              />
              <div className="text-sm text-gray-500 text-center">{fieldValue || field.min || 0}</div>
            </div>
          )

        case 'rating':
          return (
            <Rating
              value={fieldValue || 0}
              onChange={(value) => updateItem(item.id, field.name, value)}
              max={field.max || 5}
              className={field.className}
            />
          )

        case 'toggle':
          return (
            <div className="flex items-center space-x-2">
              <Toggle
                pressed={fieldValue || false}
                onPressedChange={(pressed) => updateItem(item.id, field.name, pressed)}
                className={field.className}
              >
                {field.label}
              </Toggle>
            </div>
          )

        case 'multiselect':
          return (
            <MultiSelectEnhanced
              options={field.options || []}
              value={fieldValue || []}
              onValueChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              searchable={true}
              className={field.className}
            />
          )

        case 'checkboxgroup':
          return (
            <CheckboxGroup
              options={field.options || []}
              value={fieldValue || []}
              onValueChange={(value) => updateItem(item.id, field.name, value)}
              className={field.className}
            />
          )

        case 'search':
          return (
            <SearchInput
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              onSearch={() => {}}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'simplemultiselect':
          return (
            <SimpleMultiSelect
              options={field.options || []}
              selected={fieldValue || []}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
            />
          )

        case 'tags':
          return (
            <TagsInput
              value={fieldValue || []}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'icon':
          return (
            <IconPicker
              value={fieldValue || ''}
              onChange={(value) => updateItem(item.id, field.name, value)}
              placeholder={field.placeholder}
              className={field.className}
            />
          )

        case 'file':
          return (
            <Input
              type="file"
              placeholder={field.placeholder}
              onChange={(e) => updateItem(item.id, field.name, e.target.files?.[0])}
              className={field.className}
            />
          )

        case 'color':
        case 'url':
        case 'time':
        case 'datetime':
        case 'month':
        case 'week':
          return (
            <Input
              type={field.type === 'datetime' ? 'datetime-local' : field.type}
              placeholder={field.placeholder}
              value={fieldValue || ''}
              onChange={(e) => updateItem(item.id, field.name, e.target.value)}
              className={field.className}
            />
          )

        default:
          return (
            <Input
              type={field.type}
              placeholder={field.placeholder}
              value={fieldValue || ''}
              onChange={(e) => updateItem(item.id, field.name, e.target.value)}
              className={field.className}
            />
          )
      }
    }

    return (
      <TooltipProvider>
        <div className={cn("space-y-4", className)}>
          <div className="max-h-96 overflow-y-auto space-y-4 pr-2">
            {(Array.isArray(value) ? value : []).map((item, index) => (
            <div key={`repeater-item-${item.id || index}`} className="border rounded-lg p-4 bg-gray-50/50">
              <div className="flex items-center justify-between mb-4">
                <h4 className="text-sm font-medium text-gray-700">Item {index + 1}</h4>
                {value.length > (showDefault ? Math.max(1, minItems) : minItems) && (
                  <Tooltip delayDuration={0}>
                    <TooltipTrigger asChild>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => removeItem(item.id)}
                        className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>{deleteTooltipText}</p>
                    </TooltipContent>
                  </Tooltip>
                )}
              </div>
              
              <div className={getLayoutClassName()}>
                {fields
                  .sort((a, b) => (a.layout?.order || 0) - (b.layout?.order || 0))
                  .map((field, fieldIndex) => {
                  const renderedField = renderField(field, item);
                  if (!renderedField) return null;
                  
                  const isFullWidth = field.type === 'textarea' || field.type === 'richtext' || field.type === 'tags';
                  const isCheckboxOrSwitch = field.type === 'checkbox' || field.type === 'switch';
                  
                  const getFieldClassName = () => {
                    let baseClass = isCheckboxOrSwitch ? 'flex items-center' : 'space-y-2'
                    
                    // Field-specific layout
                    if (field.layout) {
                      if (field.layout.hidden) return 'hidden'
                      
                      if (field.layout.colSpan) {
                        if (typeof field.layout.colSpan === 'object') {
                          const { sm, md, lg, xl } = field.layout.colSpan
                          if (sm) baseClass += ` col-span-${sm}`
                          if (md) baseClass += ` md:col-span-${md}`
                          if (lg) baseClass += ` lg:col-span-${lg}`
                          if (xl) baseClass += ` xl:col-span-${xl}`
                        } else {
                          baseClass += ` col-span-${field.layout.colSpan}`
                        }
                      }
                      
                      if (field.layout.order) {
                        baseClass += ` order-${field.layout.order}`
                      }
                      
                      if (field.layout.className) {
                        baseClass += ` ${field.layout.className}`
                      }
                    } else {
                      // Default layout behavior
                      if (layout?.type === 'grid' && isFullWidth && fields.length > 1) {
                        if (typeof layout.columns === 'object') {
                          const { md = 2, lg = 2 } = layout.columns
                          baseClass += ` md:col-span-${Math.min(md, 2)} lg:col-span-${Math.min(lg, 2)}`
                        } else {
                          baseClass += ' md:col-span-2 lg:col-span-2'
                        }
                      } else if (!layout && isFullWidth && fields.length > 1) {
                        baseClass += ' md:col-span-2 lg:col-span-2'
                      }
                    }
                    
                    if (layout?.type === 'flex') {
                      baseClass += ' flex-1 min-w-0'
                    }
                    
                    return baseClass
                  }
                  
                  const fieldStyle = field.layout?.width ? { width: field.layout.width } : undefined
                  
                  return (
                    <div 
                      key={`${item.id}-${field.name}-${fieldIndex}`} 
                      className={getFieldClassName()} 
                      style={fieldStyle}
                      data-repeater-field={`${item.id}-${field.name}`}
                    >
                      {!isCheckboxOrSwitch && (
                        <label className="text-sm font-medium text-gray-700">
                          {field.label}
                          {field.required && <span className="text-red-500 ml-1">*</span>}
                        </label>
                      )}
                      {renderedField}
                      {(errors[item.id]?.[field.name] || (showValidation && validationErrors[item.id]?.[field.name])) && (
                        <div className="text-red-500 text-xs mt-1">
                          {errors[item.id]?.[field.name] || validationErrors[item.id]?.[field.name]}
                        </div>
                      )}
                    </div>
                  )
                })}
              </div>
              {renderCustomField && renderCustomField(item, index, (fieldName: string, fieldValue: any) => updateItem(item.id, fieldName, fieldValue))}
            </div>
            ))}
          </div>

          {(!maxItems || value.length < maxItems) && (
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button
                  type="button"
                  variant="outline"
                  onClick={addItem}
                  className="w-full border-dashed"
                >
                  <Plus className="h-4 w-4 mr-2" />
                  {addButtonText}
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{addButtonText}</p>
              </TooltipContent>
            </Tooltip>
          )}
        </div>
      </TooltipProvider>
    )
  }
)

Repeater.displayName = "Repeater"

export { Repeater }