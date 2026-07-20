import DOMPurify from 'dompurify';

interface SafeHtmlProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'dangerouslySetInnerHTML' | 'children'> {
    html?: string | null;
}

/**
 * Renders rich-text HTML that came from a user (ticket bodies, replies, editor
 * output) after stripping scripts, event handlers, and other XSS vectors.
 *
 * Never use dangerouslySetInnerHTML directly on user-supplied content - see
 * zerp-pk/zerp#38.
 */
export function SafeHtml({ html, ...props }: SafeHtmlProps) {
    return <div {...props} dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html ?? '') }} />;
}

export default SafeHtml;
