export const getStatusBadgeClasses = (status: string) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        approved: 'bg-green-100 text-green-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800'
    };
    return `px-2 py-1 rounded-full text-sm ${colors[status as keyof typeof colors]}`;
};