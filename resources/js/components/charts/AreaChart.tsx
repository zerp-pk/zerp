import React from 'react';
import { AreaChart as RechartsAreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface AreaChartProps {
  data: any[];
  dataKey: string;
  xAxisKey: string;
  title?: string;
  color?: string;
  type?: 'monotone' | 'linear' | 'step' | 'stepBefore' | 'stepAfter';
  stacked?: boolean;
  showLegend?: boolean;
  showGrid?: boolean;
  showTooltip?: boolean;
  gradient?: boolean;
  height?: number;
  areas?: Array<{
    dataKey: string;
    color: string;
    name?: string;
  }>;
}

export const AreaChart: React.FC<AreaChartProps> = ({
  data,
  dataKey,
  xAxisKey,
  color = '#3b82f6',
  type = 'monotone',
  stacked = false,
  showLegend = false,
  showGrid = true,
  showTooltip = true,
  gradient = false,
  height = 350,
  areas = []
}) => {
  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsAreaChart data={data} margin={{ left: 12, right: 12 }}>
        {gradient && (
          <defs>
            <linearGradient id="fillDesktop" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor={color} stopOpacity={0.8}/>
              <stop offset="95%" stopColor={color} stopOpacity={0.1}/>
            </linearGradient>
          </defs>
        )}
        {showGrid && <CartesianGrid vertical={false} />}
        <XAxis dataKey={xAxisKey} tickLine={false} axisLine={false} tickMargin={8} />
        <YAxis tickLine={false} axisLine={false} tickMargin={8} />
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
        {areas.length > 0 ? areas.map((area) => (
          <Area
            key={area.dataKey}
            type={type}
            dataKey={area.dataKey}
            stackId={stacked ? "1" : undefined}
            stroke={area.color}
            fill={gradient ? "url(#fillDesktop)" : area.color}
            fillOpacity={0.4}
          />
        )) : (
          <Area
            type={type}
            dataKey={dataKey}
            stackId={stacked ? "1" : undefined}
            stroke={color}
            fill={gradient ? "url(#fillDesktop)" : color}
            fillOpacity={0.4}
          />
        )}
      </RechartsAreaChart>
    </ResponsiveContainer>
  );
};