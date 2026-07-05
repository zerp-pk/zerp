import React from 'react';

interface Option {
  value: string;
  label: string;
}

interface SimpleMultiSelectProps {
  options: Option[];
  selected: string[];
  onChange: (selected: string[]) => void;
  placeholder?: string;
}

export function SimpleMultiSelect({ options, selected, onChange, placeholder }: SimpleMultiSelectProps) {
  return (
    <div>
      {/* Simple multi-select implementation */}
      <select multiple value={selected} onChange={(e) => {
        const values = Array.from(e.target.selectedOptions, option => option.value);
        onChange(values);
      }}>
        {options.map(option => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}