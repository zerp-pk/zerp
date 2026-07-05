import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

interface CalendarEvent {
  id: number
  title: string
  startDate: string
  endDate: string
  time: string
  color: string
  [key: string]: any
}

interface CalendarViewProps {
  events: CalendarEvent[]
  onEventClick?: (event: CalendarEvent) => void
  onDateClick?: (date: Date) => void
  onMonthChange?: (month: string) => void
}

export default function CalendarView({ events, onEventClick, onDateClick, onMonthChange }: CalendarViewProps) {
  const { t } = useTranslation()
  const [isInitialized, setIsInitialized] = useState(false)



  const calendarEvents = useMemo(() => {
    return events.map(event => {
      const startDate = new Date(event.startDate);
      const endDate = new Date(event.endDate);
      
      // For FullCalendar, end date should be the day after the last day for proper spanning
      const calendarEndDate = event.startDate !== event.endDate ? 
        new Date(endDate.getTime() + 24 * 60 * 60 * 1000).toISOString().split('T')[0] : 
        undefined;
      
      return {
        id: event.id.toString(),
        title: event.title,
        start: event.startDate,
        end: calendarEndDate,
        allDay: true,
        backgroundColor: event.color,
        borderColor: event.color,
        extendedProps: event
      };
    })
  }, [events])

  const handleEventClick = (info: any) => {
    onEventClick?.(info.event.extendedProps)
  }

  const handleDateClick = (info: any) => {
    onDateClick?.(new Date(info.dateStr))
  }

  return (
    <div className="fullcalendar-container" style={{ overflow: 'visible' }}>
      <FullCalendar
        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
        initialView="dayGridMonth"
        headerToolbar={{
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        }}
        events={calendarEvents}
        height="auto"
        eventClick={handleEventClick}
        dateClick={handleDateClick}
        datesSet={(info) => {
          if (isInitialized) {
            const month = info.start.toISOString().slice(0, 7);
            onMonthChange?.(month);
          } else {
            setIsInitialized(true);
          }
        }}
        dayMaxEventRows={3}
        moreLinkText={t('more')}
        allDayText={t('All day')}
        eventMaxStack={3}
      />
      
      <style>{`
        .fullcalendar-container {
          overflow: visible !important;
        }
        
        .fullcalendar-container .fc {
          font-family: inherit;
          border: 1px solid #e2e8f0;
          border-radius: 0.5rem;
          overflow: visible !important;
          background: white;
        }
        
        .fullcalendar-container .fc-toolbar {
          margin-bottom: 0;
          padding: 1rem;
          border-bottom: 1px solid #e2e8f0;
        }
        
        @media (max-width: 768px) {
          .fullcalendar-container .fc-toolbar {
            padding: 0.5rem;
            flex-direction: column;
            gap: 0.5rem;
          }
          
          .fullcalendar-container .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
          }
          
          .fullcalendar-container .fc-button {
            padding: 0.375rem 0.5rem;
            font-size: 0.75rem;
          }
          
          .fullcalendar-container .fc-toolbar-title {
            font-size: 1rem;
            text-align: center;
          }
          
          .fullcalendar-container .fc-view-harness {
            padding: 0.5rem;
          }
          
          .fullcalendar-container .fc-daygrid-day-number {
            padding: 0.25rem;
            font-size: 0.75rem;
          }
          
          .fullcalendar-container .fc-event {
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            margin: 0.125rem;
            min-height: 1.5rem;
          }
          
          .fullcalendar-container .fc-event-title {
            font-size: 0.7rem;
          }
          
          .fullcalendar-container .fc-col-header-cell {
            padding: 0.5rem 0.25rem;
            font-size: 0.75rem;
          }
        }
        
        .fullcalendar-container .fc-toolbar-title {
          font-size: 1.25rem;
          font-weight: 600;
          color: #1f2937;
        }
        
        .fullcalendar-container .fc-button {
          background: hsl(var(--primary));
          border: 1px solid hsl(var(--primary));
          color: white;
          padding: 0.5rem 0.75rem;
          border-radius: 0.5rem;
          font-size: 0.875rem;
          text-transform: capitalize;
        }
        
        .fullcalendar-container .fc-button:hover {
          background: hsl(var(--primary) / 0.9) !important;
          border-color: hsl(var(--primary) / 0.9) !important;
        }
        
        .fullcalendar-container .fc-button-active {
          background: hsl(var(--primary)) !important;
          border-color: hsl(var(--primary)) !important;
        }
        
        .fullcalendar-container .fc-view-harness {
          padding: 1rem;
          overflow: visible !important;
        }
        
        .fullcalendar-container .fc-daygrid-day {
          cursor: pointer;
          border: 1px solid #e2e8f0;
          transition: all 0.2s ease;
          overflow: visible !important;
        }
        
        .fullcalendar-container .fc-daygrid-day:first-child {
          border-left: 1px solid #e2e8f0;
        }
        
        .fullcalendar-container .fc-daygrid-day:hover {
          background: #f8fafc;
        }
        
        .fullcalendar-container .fc-daygrid-day-number {
          color: #475569;
          font-weight: 600;
          padding: 0.5rem;
          font-size: 0.875rem;
        }
        
        .fullcalendar-container .fc-event {
          border-radius: 0.375rem;
          padding: 0.375rem 0.75rem;
          font-size: 0.8rem;
          border: none;
          margin: 0.25rem;
          min-height: 2rem;
          display: flex;
          align-items: center;
          cursor: pointer;
          transition: opacity 0.2s ease;
        }
        
        .fullcalendar-container .fc-event:hover {
          opacity: 0.9;
        }
        
        .fullcalendar-container .fc-daygrid-event {
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          width: 100%;
        }
        
        .fullcalendar-container .fc-event-title {
          font-weight: 600;
          font-size: 0.8rem;
          line-height: 1.3;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          width: 100%;
          color: white !important;
        }
        
        .fullcalendar-container .fc-event-main {
          width: 100%;
          display: flex;
          align-items: center;
        }
        
        .fullcalendar-container .fc-event-main-frame {
          width: 100%;
          display: flex;
          align-items: center;
        }
        
        .fullcalendar-container .fc-day-today {
          background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
          border: 2px solid rgba(102, 126, 234, 0.3) !important;
        }
        
        .fullcalendar-container .fc-col-header-cell {
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          font-weight: 600;
          color: #374151;
          padding: 0.75rem 0.5rem;
          font-size: 0.875rem;
        }
        
        .fullcalendar-container .fc-col-header-cell:first-child {
          border-left: 1px solid #e2e8f0;
        }
        
        .fullcalendar-container .fc-popover {
          z-index: 9999 !important;
          max-height: 400px;
        }
        
        .fullcalendar-container .fc-more-popover {
          z-index: 9999 !important;
          max-height: 400px;
        }
        
        .fullcalendar-container .fc-popover-body {
          max-height: 350px;
          overflow-y: auto;
          overflow-x: hidden;
        }
        
        .fullcalendar-container .fc-daygrid-event-harness {
          margin-bottom: 2px;
        }
        
        .fullcalendar-container .fc-daygrid-body,
        .fullcalendar-container .fc-scrollgrid,
        .fullcalendar-container .fc-scrollgrid-section,
        .fullcalendar-container .fc-scroller {
          overflow: visible !important;
        }
        
        .fullcalendar-container .fc-popover-body::-webkit-scrollbar {
          width: 6px;
        }
        
        .fullcalendar-container .fc-popover-body::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 3px;
        }
        
        .fullcalendar-container .fc-popover-body::-webkit-scrollbar-thumb {
          background: #888;
          border-radius: 3px;
        }
        
        .fullcalendar-container .fc-popover-body::-webkit-scrollbar-thumb:hover {
          background: #555;
        }
      
      `}</style>
    </div>
  )
}