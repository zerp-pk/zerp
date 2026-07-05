"use client";

import { useState, useMemo } from "react";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import emojiData from "unicode-emoji-json"; // npm install unicode-emoji-json

interface EmojiPickerProps {
  onEmojiSelect: (emoji: string) => void;
  className?: string;
}

export function EmojiPicker({ onEmojiSelect, className = "" }: EmojiPickerProps) {
  const [search, setSearch] = useState("");

  // Convert emojiData (object) → grouped by category
  const categorized = useMemo(() => {
    const groups: Record<string, { emoji: string; name: string }[]> = {};

    Object.entries(emojiData).forEach(([emoji, info]) => {
      const category = info.group || "Other";
      if (!groups[category]) groups[category] = [];
      groups[category].push({ emoji, name: info.name });
    });

    return groups;
  }, []);

  // Filter by search
  const filtered = useMemo(() => {
    if (!search) return categorized;

    const lower = search.toLowerCase();
    const result: typeof categorized = {};

    Object.entries(categorized).forEach(([cat, items]) => {
      const matched = items.filter(
        (e) => e.name.toLowerCase().includes(lower) || e.emoji.includes(lower)
      );
      if (matched.length) result[cat] = matched;
    });

    return result;
  }, [search, categorized]);

  return (
    <div
      className={`bg-background border rounded-lg shadow-lg p-4 z-10 w-80 ${className}`}
    >
      {/* Search input */}
      <input
        type="text"
        placeholder="Search emoji..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="mb-3 w-full rounded-md border px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-300"
      />

      {/* Make all categories scroll together */}
      <ScrollArea className="h-64 w-full pr-2">
        <div className="space-y-4">
          {Object.entries(filtered).map(([category, emojis]) => (
            <div key={category}>
              <h4 className="text-sm font-medium text-gray-700 mb-2">
                {category}
              </h4>
              <div className="grid grid-cols-8 gap-1">
                {emojis.map(({ emoji }, index) => (
                  <Button
                    key={index}
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onEmojiSelect(emoji)}
                    className="h-8 w-8 p-0 text-lg hover:bg-gray-100"
                  >
                    {emoji}
                  </Button>
                ))}
              </div>
            </div>
          ))}
        </div>
      </ScrollArea>
    </div>
  );
}
