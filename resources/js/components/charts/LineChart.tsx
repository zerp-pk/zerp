import React from 'react';
import { LineChart as RechartsLineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface LineChartProps {
  data: any[];
  dataKey: string;
  xAxisKey: string;
  color?: string;
  type?: 'monotone' | 'linear' | 'step' | 'stepBefore' | 'stepAfter';
  showLegend?: boolean;
  showGrid?: boolean;
  showTooltip?: boolean;
  showDots?: boolean;
  height?: number;
  lines?: Array<{
    dataKey: string;
    color: string;
    name?: string;
    type?: 'monotone' | 'linear' | 'step' | 'stepBefore' | 'stepAfter';
  }>;
  customDots?: boolean;
  strokeWidth?: number;
}

export const LineChart: React.FC<LineChartProps> = ({
  data,
  dataKey,
  xAxisKey,
  color = '#3b82f6',
  type = 'monotone',
  showLegend = false,
  showGrid = true,
  showTooltip = true,
  showDots = false,
  height = 350,
  lines = [],
  customDots = false,
  strokeWidth = 2
}) => {
  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsLineChart data={data} margin={{ left: 12, right: 12 }}>
        {showGrid && <CartesianGrid vertical={false} />}
        <XAxis dataKey={xAxisKey} tickLine={false} axisLine={false} tickMargin={8} />
        <YAxis tickLine={false} axisLine={false} tickMargin={8} />
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
        {lines.length > 0 ? lines.map((line) => (
          <Line
            name ={line.name || line.dataKey}
            key={line.dataKey}
            type={line.type || type}
            dataKey={line.dataKey}
            stroke={line.color}
            strokeWidth={strokeWidth}
            dot={showDots}
          />
        )) : (
          <Line
            type={type}
            dataKey={dataKey}
            stroke={color}
            strokeWidth={strokeWidth}
            dot={showDots}
          />
        )}
      </RechartsLineChart>
    </ResponsiveContainer>
  );
};
