export interface Message {
    id: number;
    sender_id: number;
    receiver_id: number;
    message: string;
    is_read: boolean;
    created_at: string;
    updated_at: string;
    sender: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
    receiver: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
}

export interface ChatUser {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    last_message?: Message;
    unread_count: number;
    is_online: boolean;
}

export interface MessengerIndexProps {
    users: ChatUser[];
    messages: Message[];
    selectedUserId?: number;
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            avatar?: string;
            permissions?: string[];
        };
    };
}