"use client"

import * as React from "react"
import {
  Command,
  Frame, Home,
  LifeBuoy,
  Send,
  SquareTerminal,
  Search,
} from "lucide-react"

import { NavMain } from "@/components/nav-main"
import { NavSecondary } from "@/components/nav-secondary"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarInput,
} from "@/components/ui/sidebar"
import {Link, usePage} from "@inertiajs/react";
import {PageProps} from "@/types";
import { allMenuItems } from "@/utils/menu";
import { useTranslation } from 'react-i18next';
import { useBrand } from "@/contexts/brand-context";



export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { auth } = usePage<PageProps>().props;
    const { t } = useTranslation();
    const { settings, getCompleteSidebarProps } = useBrand();
    const [searchQuery, setSearchQuery] = React.useState("");

    const sidebarProps = getCompleteSidebarProps();

    return (
    <Sidebar
        variant={settings.sidebarVariant as any}
        side={settings.layoutDirection === 'rtl' ? 'right' : 'left'}
        collapsible="icon"
        className={sidebarProps.className}
        style={sidebarProps.style}
        {...props}
    >
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={route('dashboard')} className="flex items-center !py-4 h-auto justify-center">
                {/* Logo for expanded sidebar */}
                <div className="group-data-[collapsible=icon]:hidden flex items-center">
                  {(() => {
                    const isDark = document.documentElement.classList.contains('dark');
                    const currentLogo = isDark ? settings.logo_light : settings.logo_dark;
                    const { getPreviewUrl } = useBrand();
                    const displayUrl = currentLogo ? getPreviewUrl(currentLogo) : '';

                    return displayUrl ? (
                      <img
                        src={displayUrl}
                        alt="Logo"
                        className="h-10 w-auto max-w-[180px] object-contain transition-all duration-200"
                      />
                    ) : (
                      <div className="h-12 text-inherit font-semibold flex items-center text-lg tracking-tight">
                        {settings.titleText || 'Zerp'}
                      </div>
                    );
                  })()}
                </div>

                {/* Icon for collapsed sidebar */}
                <div className="h-8 w-8 hidden group-data-[collapsible=icon]:block">
                  {(() => {
                    const { getPreviewUrl } = useBrand();
                    const displayFavicon = settings.favicon ? getPreviewUrl(settings.favicon) : '';

                    return displayFavicon ? (
                      <img
                        src={displayFavicon}
                        alt="Icon"
                        className="h-8 w-8 transition-all duration-200"
                      />
                    ) : (
                      <div className="h-8 w-8 bg-primary text-white rounded flex items-center justify-center font-bold shadow-sm">
                        W
                      </div>
                    );
                  })()}
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
        <div className="px-2 group-data-[collapsible=icon]:px-2">
          <div className="relative">
            <Search className="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground group-data-[collapsible=icon]:hidden" />
            <SidebarInput
              placeholder="Search menu..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-8 group-data-[collapsible=icon]:hidden border-sidebar-border focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary"
            />
          </div>
        </div>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={allMenuItems()} searchQuery={searchQuery} />
      </SidebarContent>
    </Sidebar>
  )
}
