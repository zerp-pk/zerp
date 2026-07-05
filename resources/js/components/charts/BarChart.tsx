import React from 'react';
import { BarChart as RechartsBarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell } from 'recharts';

interface BarChartProps {
  data: any[];
  dataKey: string;
  xAxisKey: string;
  color?: string;
  horizontal?: boolean;
  stacked?: boolean;
  showLegend?: boolean;
  showGrid?: boolean;
  showTooltip?: boolean;
  height?: number;
  bars?: Array<{
    dataKey: string;
    color: string;
    name?: string;
  }>;
  activeIndex?: number;
  negative?: boolean;
}

export const BarChart: React.FC<BarChartProps> = ({
  data,
  dataKey,
  xAxisKey,
  color = '#3b82f6',
  horizontal = false,
  stacked = false,
  showLegend = false,
  showGrid = true,
  showTooltip = true,
  height = 350,
  bars = [],
  activeIndex,
  negative = false
}) => {
  const layout = horizontal ? { layout: 'horizontal' as const } : {};

  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsBarChart data={data} margin={horizontal ? { left: 80, right: 12 } : { left: 12, right: 12 }} {...layout}>
        {showGrid && <CartesianGrid vertical={false} />}
        {horizontal ? (
          <>
            <XAxis type="number" domain={negative ? ['dataMin', 'dataMax'] : [0, 'dataMax']} tickLine={false} axisLine={false} />
            <YAxis type="category" dataKey={xAxisKey} tickLine={false} axisLine={false} width={70} />
          </>
        ) : (
          <>
            <XAxis dataKey={xAxisKey} tickLine={false} axisLine={false} tickMargin={8} />
            <YAxis domain={negative ? ['dataMin', 'dataMax'] : [0, 'dataMax']} tickLine={false} axisLine={false} tickMargin={8} />
          </>
        )}
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
        {bars.length > 0 ? bars.map((bar) => (
          <Bar
            key={bar.dataKey}
            dataKey={bar.dataKey}
            stackId={stacked ? "1" : undefined}
            fill={bar.color}
            radius={4}
          />
        )) : (
          <Bar dataKey={dataKey} fill={color} radius={4}>
            {activeIndex !== undefined && data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={index === activeIndex ? '#10b77f' : color} />
            ))}
          </Bar>
        )}
      </RechartsBarChart>
    </ResponsiveContainer>
  );
};