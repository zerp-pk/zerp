import { HelpdeskReply, CreateHelpdeskReplyFormData } from '../tickets/types';

export interface ChatMessageProps {
    reply: HelpdeskReply;
    isOwnMessage: boolean;
    onDelete?: (replyId: number) => void;
    canDelete?: boolean;
}

export interface ReplyFormProps {
    ticketId: number;
    onReplyAdded: (reply: HelpdeskReply) => void;
    disabled?: boolean;
}