"use client"

import {
  BadgeCheck,
  ChevronsUpDown,
  LogOut,
  Moon,
  Sun,
  Monitor,
} from "lucide-react"
import { useTranslation } from 'react-i18next'
import { LanguageSwitcher } from './language-switcher'

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import {User} from "@/types";
import {Link, router, usePage} from "@inertiajs/react";
import {useTheme} from "next-themes";
import { Button } from "@/components/ui/button";
import { getImagePath } from "@/utils/helpers";
import React from "react";
import { PageProps } from "@/types";



export function NavUser({
  user,
  inHeader = false,
}: {
  user: User
  inHeader?: boolean
}) {
  const { isMobile } = useSidebar()
  const {setTheme} = useTheme()
  const { i18n, t } = useTranslation()
  const { auth } = usePage<PageProps>().props



  if (inHeader) {
    return (
      <div className="flex items-center gap-2">
        <LanguageSwitcher />

        <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="flex items-center gap-2 h-8 px-3 rounded-md">
            <Avatar className="h-8 w-8 rounded-full">
              {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
              <AvatarFallback className="bg-muted rounded-full">{user.name?.charAt(0)?.toUpperCase()}</AvatarFallback>
            </Avatar>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>
            <div className="flex flex-col space-y-1">
              <p className="text-sm font-medium">{user.name}</p>
              <p className="text-xs text-muted-foreground">{user.email}</p>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuGroup>
            {auth.user?.permissions?.includes('manage-profile') && (
              <DropdownMenuItem asChild>
                <Link href={route('profile.edit')}>
                  <BadgeCheck className="mr-2 h-4 w-4" />
                  {t('Edit Profile')}
                </Link>
              </DropdownMenuItem>
            )}
          </DropdownMenuGroup>
          <DropdownMenuSeparator />
          <DropdownMenuItem asChild>
            <Link
              className="w-full"
              href={route("logout")}
              method={"post"}
              as={"button"}
            >
              <LogOut className="mr-2 h-4 w-4" />
              {t('Log out')}
            </Link>
          </DropdownMenuItem>
        </DropdownMenuContent>
        </DropdownMenu>
      </div>
    )
  }

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
              <Avatar className="h-8 w-8 rounded-md">
                {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                <AvatarFallback className="rounded-md">{user.name?.charAt(0)?.toUpperCase()}</AvatarFallback>
              </Avatar>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">{user.name}</span>
                <span className="truncate text-xs">{user.email}</span>
              </div>
              <ChevronsUpDown className="ml-auto size-4" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            side={isMobile ? "bottom" : "right"}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuLabel className="p-0 font-normal">
              <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                <Avatar className="h-8 w-8 rounded-md">
                  {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                  <AvatarFallback className="rounded-md">{user.name?.charAt(0)?.toUpperCase()}</AvatarFallback>
                </Avatar>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">{user.name}</span>
                  <span className="truncate text-xs">{user.email}</span>
                </div>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              {auth.user?.permissions?.includes('manage-profile') && (
                <DropdownMenuItem asChild>
                  <Link href={route('profile.edit')}>
                    <BadgeCheck className="mr-2 h-4 w-4" />
                    {t('Edit Profile')}
                  </Link>
                </DropdownMenuItem>
              )}
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem onClick={() => setTheme("light")}>
                <Sun className="mr-2 h-4 w-4" />
                {t('Light')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setTheme("dark")}>
                <Moon className="mr-2 h-4 w-4" />
                {t('Dark')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setTheme("system")}>
                <Monitor className="mr-2 h-4 w-4" />
                {t('System')}
              </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuLabel className="px-2 py-1.5 text-sm font-semibold">{t('Language')}</DropdownMenuLabel>
              <div className="px-2">
                <LanguageSwitcher />
              </div>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />

            <DropdownMenuItem asChild>
              <Link
                  className="w-full"
                  href={route("logout")}
                  method={"post"}
                  as={"button"}
              >
                <LogOut className="mr-2 h-4 w-4" />
                {t('Log out')}
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
