import { useState, useEffect, forwardRef } from 'react'
import { useTranslation } from 'react-i18next'

export interface KanbanTask {
  id: number
  title: string
  description?: string
  priority?: 'low' | 'medium' | 'high' | 'urgent'
  assigned_to?: { name: string; avatar?: string | null } | null
  due_date?: string
  [key: string]: any
}

export interface KanbanColumn {
  id: string
  title: string
  color: string
}

export interface KanbanBoardProps {
  tasks: Record<string, KanbanTask[]>
  columns: KanbanColumn[]
  onMove?: (taskId: number, fromStatus: string, toStatus: string) => void
  kanbanActions?: React.ReactNode | ((columnId: string) => React.ReactNode)
  taskCard?: React.ComponentType<{ task: KanbanTask }>
}





function KanbanColumnComponent({ 
  column, 
  tasks, 
  onMove,
  kanbanActions,
  TaskCard
}: { 
  column: KanbanColumn
  tasks: KanbanTask[]
  onMove?: (taskId: number, fromStatus: string, toStatus: string) => void
  kanbanActions?: React.ReactNode | ((columnId: string) => React.ReactNode)
  TaskCard?: React.ComponentType<{ task: KanbanTask }>
}) {
  const { t } = useTranslation()
  const [isDragOver, setIsDragOver] = useState(false)
  
  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    e.dataTransfer.dropEffect = 'move'
    setIsDragOver(true)
  }
  
  const handleDragEnter = (e: React.DragEvent) => {
    e.preventDefault()
    setIsDragOver(true)
  }
  
  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault()
    if (!e.currentTarget.contains(e.relatedTarget as Node)) {
      setIsDragOver(false)
    }
  }
  
  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragOver(false)
    
    try {
      const data = JSON.parse(e.dataTransfer.getData('application/json'))
      if (data.taskId && onMove) {
        onMove(data.taskId, '', column.id)
      }
    } catch (error) {
      console.error('Error parsing drag data:', error)
    }
  }
  
  return (
    <div 
      className="flex-1 min-w-72 max-w-80"
      onDragOver={handleDragOver}
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      <div className={`h-full rounded-lg ${isDragOver ? 'ring-2 ring-blue-400 bg-blue-50' : ''} transition-all`} style={{ backgroundColor: `${column.color}10` }}>
        <div className="px-3 py-2 rounded-t-lg border-b border-white/50" style={{ backgroundColor: `${column.color}20` }}>
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <h3 className="text-sm font-semibold" style={{ color: column.color }}>{column.title}</h3>
              <span className="text-xs px-2 py-0.5 rounded-full bg-white/70 font-medium" style={{ color: column.color }}>
                {tasks.length}
              </span>
            </div>
            {typeof kanbanActions === 'function' ? kanbanActions(column.id) : kanbanActions}
          </div>
        </div>
        
        <div className="p-2 min-h-[700px] max-h-[calc(100vh-200px)] overflow-y-auto">
          {tasks.map((task) => (
            TaskCard ? <TaskCard key={task.id} task={task} /> : <div key={task.id}>{task.title}</div>
          ))}
          
          {tasks.length === 0 && (
            <div className="text-center py-12 text-gray-400">
              <p className="text-xs">{t('Drop tasks here')}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

const KanbanBoard = forwardRef<any, KanbanBoardProps>(function KanbanBoard({
  tasks: initialTasks,
  columns,
  onMove,
  kanbanActions,
  taskCard: TaskCard
}, ref) {
  const { t } = useTranslation()
  const [tasks, setTasks] = useState(initialTasks)

  useEffect(() => {
    setTasks(initialTasks)
  }, [initialTasks])

  
  const handleMove = (taskId: number, fromStatus: string, toStatus: string) => {
    setTasks(prevTasks => {
      const newTasks = { ...prevTasks }
      let movedTask: KanbanTask | null = null
      
      Object.keys(newTasks).forEach(status => {
        const taskIndex = newTasks[status].findIndex(task => task.id === taskId)
        if (taskIndex !== -1) {
          movedTask = newTasks[status][taskIndex]
          newTasks[status] = newTasks[status].filter(task => task.id !== taskId)
        }
      })
      
      if (movedTask) {
        newTasks[toStatus] = [...(newTasks[toStatus] || []), movedTask]
      }
      
      return newTasks
    })
    
    if (onMove) {
      onMove(taskId, fromStatus, toStatus)
    }
  }
  

  

  
  return (
    <div className="flex space-x-6 overflow-x-auto pb-6">
      {columns.map((column) => (
        <KanbanColumnComponent
          key={column.id}
          column={column}
          tasks={tasks[column.id] || []}
          onMove={handleMove}
          kanbanActions={kanbanActions}
          TaskCard={TaskCard}
        />
      ))}
    </div>
  )
})

export default KanbanBoard