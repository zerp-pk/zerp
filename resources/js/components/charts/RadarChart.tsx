import React from 'react';
import { RadarChart as RechartsRadarChart, PolarGrid, PolarAngleAxis, PolarRadiusAxis, Radar, ResponsiveContainer, Legend, Tooltip } from 'recharts';

interface RadarChartProps {
  data: any[];
  dataKey: string;
  angleKey: string;
  title?: string;
  color?: string;
  showLegend?: boolean;
  showTooltip?: boolean;
  showDots?: boolean;
  height?: number;
  radars?: Array<{
    dataKey: string;
    color: string;
    name?: string;
  }>;
  gridType?: 'polygon' | 'circle';
  showGrid?: boolean;
  showLines?: boolean;
  fillOpacity?: number;
  strokeWidth?: number;
}

export const RadarChart: React.FC<RadarChartProps> = ({
  data,
  dataKey,
  angleKey,
  title,
  color = '#3b82f6',
  showLegend = false,
  showTooltip = false,
  showDots = false,
  height = 350,
  radars = [],
  gridType = 'polygon',
  showGrid = true,
  showLines = true,
  fillOpacity = 0.6,
  strokeWidth = 2
}) => {
  const renderRadars = () => {
    if (radars.length > 0) {
      return radars.map((radar) => (
        <Radar
          key={radar.dataKey}
          name={radar.name || radar.dataKey}
          dataKey={radar.dataKey}
          stroke={radar.color}
          fill={radar.color}
          fillOpacity={fillOpacity}
          strokeWidth={strokeWidth}
          dot={showDots}
        />
      ));
    }
    
    return (
      <Radar
        dataKey={dataKey}
        stroke={color}
        fill={color}
        fillOpacity={fillOpacity}
        strokeWidth={strokeWidth}
        dot={showDots}
      />
    );
  };

  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsRadarChart data={data} margin={{ top: 20, right: 20, bottom: 20, left: 20 }}>
        {showGrid && (
          <PolarGrid 
            gridType={gridType}
            radialLines={showLines}
          />
        )}
        <PolarAngleAxis dataKey={angleKey} tick={{ fontSize: 12 }} />
        <PolarRadiusAxis angle={90} domain={[0, 'dataMax']} tick={false} />
        {renderRadars()}
        {showTooltip && <Tooltip />}
        {showLegend && <Legend />}
      </RechartsRadarChart>
    </ResponsiveContainer>
  );
};