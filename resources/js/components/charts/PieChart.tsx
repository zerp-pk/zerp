import React from 'react';
import { PieChart as RechartsPieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend, LabelList } from 'recharts';

interface PieChartProps {
  data: any[];
  dataKey: string;
  nameKey: string;
  height?: number;
  donut?: boolean;
  innerRadius?: number;
  outerRadius?: number;
  separatorNone?: boolean;
  activeIndex?: number;
  centerText?: string;
  showLabels?: boolean;
  showLegend?: boolean;
  showTooltip?: boolean;
}

const COLORS = ['#3b82f6', '#10b77f', '#f59e0b', '#ef4444', '#8b5cf6'];

export const PieChart: React.FC<PieChartProps> = ({
  data,
  dataKey,
  nameKey,
  height = 350,
  donut = false,
  innerRadius = 0,
  outerRadius = 80,
  separatorNone = false,
  activeIndex,
  centerText,
  showLabels = false,
  showLegend = false,
  showTooltip = false
}) => {
  // Handle empty or invalid data
  if (!data || !Array.isArray(data) || data.length === 0) {
    return (
      <div className="flex items-center justify-center h-full text-muted-foreground">
        No data available
      </div>
    );
  }

  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsPieChart>
        <Pie
          data={data}
          cx="50%"
          cy="50%"
          innerRadius={donut ? (innerRadius || 60) : innerRadius}
          outerRadius={outerRadius}
          paddingAngle={separatorNone ? 0 : 5}
          dataKey={dataKey}
          nameKey={nameKey}
          label={showLabels}
        >
          {data.map((entry, index) => (
            <Cell 
              key={`cell-${index}`} 
              fill={entry.color || COLORS[index % COLORS.length]}
              stroke={activeIndex === index ? '#333' : 'none'}
              strokeWidth={activeIndex === index ? 2 : 0}
            />
          ))}
        </Pie>
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
      </RechartsPieChart>
    </ResponsiveContainer>
  );
};