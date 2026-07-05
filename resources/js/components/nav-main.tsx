import { Link, usePage } from '@inertiajs/react';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuItem, SidebarMenuButton, SidebarMenuSub, SidebarMenuSubItem, SidebarMenuSubButton } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown } from 'lucide-react';
import { NavItem } from '@/types';

export function NavMain({ items = [], searchQuery = "" }: { items: NavItem[], searchQuery?: string }) {
    const page = usePage();

    // Filter items based on search query
    const filterItems = (items: NavItem[], query: string): NavItem[] => {
        if (!query) return items;
        
        return items.reduce((acc, item) => {
            const matchesTitle = item.title.toLowerCase().includes(query.toLowerCase());
            const filteredChildren = item.children ? filterItems(item.children, query) : [];
            
            if (matchesTitle || filteredChildren.length > 0) {
                acc.push({
                    ...item,
                    children: filteredChildren.length > 0 ? filteredChildren : item.children
                });
            }
            return acc;
        }, [] as NavItem[]);
    };

    const filteredItems = filterItems(items, searchQuery);

    // Helper function to check if URL matches (exact or starts with for detail pages)
    const isUrlActive = (itemPath: string, activePaths?: string[], exact = false): boolean => {
        const currentPath = page.url.split('?')[0];

        if (exact) {
            if (currentPath === itemPath) return true;
        } else {
            if (currentPath === itemPath || currentPath.startsWith(itemPath + '/')) return true;
        }

        if (activePaths) {
            return activePaths.some(p => {
                try {
                    const pathToCheck = p.startsWith('http') ? new URL(p).pathname : p;
                    if (exact) return currentPath === pathToCheck;
                    return currentPath === pathToCheck || currentPath.startsWith(pathToCheck + '/');
                } catch {
                    return currentPath.includes(p);
                }
            });
        }
        return false;
    };

    // Helper function to check if any child is active (recursive for nested children)
    const isChildActive = (children: NavItem[]): boolean => {
        return children.some(child => {
            if (child.href) {
                const childPath = new URL(child.href, window.location.origin).pathname;
                return isUrlActive(childPath, child.activePaths);
            }
            if (child.activePaths) {
                return isUrlActive('', child.activePaths);
            }
            if (child.children) {
                return isChildActive(child.children);
            }
            return false;
        });
    };

    return (
        <SidebarGroup>
            <SidebarMenu>
                {filteredItems.map((item) => {
                  const itemPath = item.href ? new URL(item.href, window.location.origin).pathname : '';
                  const isActive = !!(itemPath && isUrlActive(itemPath));

                  // Check if any child is active for parent menus
                  const hasActiveChild = item.children ? isChildActive(item.children) : false;
                  const shouldBeActive = isActive || hasActiveChild;
                    if (item.children && item.children.length > 0) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                {/* Expanded sidebar - use collapsible */}
                                <Collapsible asChild defaultOpen={shouldBeActive} className="group/collapsible group-data-[collapsible=icon]:hidden">
                                    <div>
                                        <CollapsibleTrigger asChild>
                                            <SidebarMenuButton tooltip={item.title} isActive={shouldBeActive} data-current={false}>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                                <ChevronDown className="ml-auto h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                            </SidebarMenuButton>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <SidebarMenuSub>
                                                {item.children.map((subItem) => {
                                                    const subItemActive = !!(subItem.href && isUrlActive(new URL(subItem.href, window.location.origin).pathname, subItem.activePaths));
                                                    const hasActiveSubChild = subItem.children ? isChildActive(subItem.children) : false;
                                                    const subItemShouldBeActive = subItemActive || hasActiveSubChild;
                                                    
                                                    if (subItem.children && subItem.children.length > 0) {
                                                        return (
                                                            <SidebarMenuSubItem key={subItem.title}>
                                                                <Collapsible asChild defaultOpen={subItemShouldBeActive} className="group/subcollapsible">
                                                                    <div>
                                                                        <CollapsibleTrigger asChild>
                                                                            <SidebarMenuSubButton isActive={subItemShouldBeActive} data-current={false}>
                                                                                {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                                <span>{subItem.title}</span>
                                                                                <ChevronDown className="ml-auto h-3 w-3 transition-transform group-data-[state=open]/subcollapsible:rotate-180" />
                                                                            </SidebarMenuSubButton>
                                                                        </CollapsibleTrigger>
                                                                        <CollapsibleContent>
                                                                            <SidebarMenuSub>
                                                                                {subItem.children.map((subSubItem) => {
                                                                                    const isSubSubActive = !!(subSubItem.href && isUrlActive(new URL(subSubItem.href, window.location.origin).pathname, subSubItem.activePaths));
                                                                                    return (
                                                                                    <SidebarMenuSubItem key={subSubItem.title}>
                                                                                        <SidebarMenuSubButton
                                                                                            asChild
                                                                                            isActive={isSubSubActive}
                                                                                                data-current={isSubSubActive}
                                                                                            className="text-sm"
                                                                                        >
                                                                                            <Link href={subSubItem.href!}>
                                                                                                {subSubItem.icon && <subSubItem.icon className="h-3 w-3" />}
                                                                                                <span>{subSubItem.title}</span>
                                                                                            </Link>
                                                                                        </SidebarMenuSubButton>
                                                                                    </SidebarMenuSubItem>
                                                                                );
                                                                                })}</SidebarMenuSub>
                                                                        </CollapsibleContent>
                                                                    </div>
                                                                </Collapsible>
                                                            </SidebarMenuSubItem>
                                                        );
                                                    }
                                                    
                                                    return (
                                                        <SidebarMenuSubItem key={subItem.title}>
                                                            <SidebarMenuSubButton
                                                                asChild
                                                                isActive={subItemActive}
                                                                data-current={subItemActive}
                                                            >
                                                                <Link href={subItem.href!}>
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                    <span>{subItem.title}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    );
                                                })}
                                            </SidebarMenuSub>
                                        </CollapsibleContent>
                                    </div>
                                </Collapsible>
                                
                                {/* Collapsed sidebar - use dropdown */}
                                <div className="hidden group-data-[collapsible=icon]:block" onMouseEnter={(e) => e.stopPropagation()} onMouseLeave={(e) => e.stopPropagation()}>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <SidebarMenuButton
                                                tooltip={item.title}
                                                isActive={shouldBeActive}
                                            >
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </SidebarMenuButton>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="right" align="start" className="w-48">
                                            {item.children.map((subItem) => {
                                                if (subItem.children && subItem.children.length > 0) {
                                                    return (
                                                        <DropdownMenu key={subItem.title}>
                                                            <DropdownMenuTrigger asChild>
                                                                <DropdownMenuItem className="flex items-center gap-2 cursor-pointer">
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                    <span>{subItem.title}</span>
                                                                    <ChevronDown className="ml-auto h-3 w-3" />
                                                                </DropdownMenuItem>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent side="right" align="start" className="w-44">
                                                                {subItem.children.map((subSubItem) => (
                                                                    <DropdownMenuItem key={subSubItem.title} asChild>
                                                                        <Link href={subSubItem.href!} className="flex items-center gap-2">
                                                                            {subSubItem.icon && <subSubItem.icon className="h-3 w-3" />}
                                                                            <span className="text-sm">{subSubItem.title}</span>
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                ))}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    );
                                                }
                                                
                                                return (
                                                    <DropdownMenuItem key={subItem.title} asChild>
                                                        <Link href={subItem.href!} className="flex items-center gap-2">
                                                            {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                            <span>{subItem.title}</span>
                                                        </Link>
                                                    </DropdownMenuItem>
                                                );
                                            })}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={shouldBeActive}
                                data-current={false}
                                tooltip={item.title}
                            >
                                <Link href={item.href!}>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}