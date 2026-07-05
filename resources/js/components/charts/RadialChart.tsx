import React from 'react';
import { RadialBarChart, RadialBar, ResponsiveContainer, Legend, Tooltip, Cell } from 'recharts';

interface RadialChartProps {
  data: any[];
  dataKey: string;
  title?: string;
  colors?: string[];
  showLegend?: boolean;
  showTooltip?: boolean;
  height?: number;
  innerRadius?: number;
  outerRadius?: number;
  showLabels?: boolean;
  centerText?: string;
  shape?: 'round' | 'square';
  stacked?: boolean;
}

const COLORS = ['#3b82f6', '#10b77f', '#f59e0b', '#ef4444', '#8b5cf6'];

export const RadialChart: React.FC<RadialChartProps> = ({
  data,
  dataKey,
  title,
  colors = COLORS,
  showLegend = false,
  showTooltip = true,
  height = 350,
  innerRadius = 40,
  outerRadius = 110,
  showLabels = false,
  centerText,
  shape = 'round',
  stacked = false
}) => {
  const processedData = data.map((item, index) => ({
    ...item,
    fill: colors[index % colors.length]
  }));

  return (
    <ResponsiveContainer width="100%" height={height}>
      <RadialBarChart 
        cx="50%" 
        cy="50%" 
        innerRadius={innerRadius} 
        outerRadius={outerRadius} 
        data={processedData}
        margin={{ top: 20, right: 20, bottom: 20, left: 20 }}
      >
        <RadialBar
          dataKey={dataKey}
          cornerRadius={shape === 'round' ? 10 : 0}
          fill="#3b82f6"
          label={showLabels ? { position: 'insideStart', fill: '#fff', fontSize: 12 } : false}
        >
          {processedData.map((entry, index) => (
            <Cell key={`cell-${index}`} fill={entry.fill} />
          ))}
        </RadialBar>
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
        {centerText && (
          <text x="50%" y="50%" textAnchor="middle" dominantBaseline="middle" className="text-xs font-medium fill-gray-700">
            {centerText}
          </text>
        )}
      </RadialBarChart>
    </ResponsiveContainer>
  );
};