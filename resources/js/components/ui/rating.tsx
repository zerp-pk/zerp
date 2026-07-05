import { useState } from 'react';
import { Star } from 'lucide-react';

interface RatingProps {
    value: number;
    onChange: (value: number) => void;
    max?: number;
    size?: 'sm' | 'md' | 'lg';
    readonly?: boolean;
    className?: string;
}

export function Rating({ value, onChange, max = 5, size = 'md', readonly = false, className }: RatingProps) {
    const [hoverValue, setHoverValue] = useState(0);

    const sizeClasses = {
        sm: 'h-4 w-4',
        md: 'h-5 w-5',
        lg: 'h-6 w-6'
    };

    const handleClick = (rating: number) => {
        if (!readonly) {
            onChange(rating);
        }
    };

    const handleMouseEnter = (rating: number) => {
        if (!readonly) {
            setHoverValue(rating);
        }
    };

    const handleMouseLeave = () => {
        if (!readonly) {
            setHoverValue(0);
        }
    };

    return (
        <div className={`flex items-center space-x-1 ${className}`}>
            {Array.from({ length: max }, (_, index) => {
                const rating = index + 1;
                const isFilled = rating <= (hoverValue || value);
                
                return (
                    <Star
                        key={index}
                        className={`${sizeClasses[size]} ${
                            readonly ? 'cursor-default' : 'cursor-pointer'
                        } ${
                            isFilled 
                                ? 'fill-yellow-400 text-yellow-400' 
                                : 'text-gray-300 hover:text-yellow-400'
                        } transition-colors`}
                        onClick={() => handleClick(rating)}
                        onMouseEnter={() => handleMouseEnter(rating)}
                        onMouseLeave={handleMouseLeave}
                    />
                );
            })}
        </div>
    );
}