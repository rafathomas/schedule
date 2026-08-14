import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BriefcaseBusiness,
    BellRing,
    CalendarDays,
    Clock3,
    LayoutDashboard,
    Palette,
    Scissors,
    Settings2,
    UserRound,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Agenda',
        href: '/agenda',
        icon: CalendarDays,
    },
    {
        title: 'Clientes',
        href: '/clientes',
        icon: UsersRound,
    },
    {
        title: 'Profissionais',
        href: '/profissionais',
        icon: UserRound,
    },
    {
        title: 'Serviços',
        href: '/servicos',
        icon: Scissors,
    },
    {
        title: 'Horários',
        href: '/horarios',
        icon: Clock3,
    },
    {
        title: 'Comunicação',
        href: '/comunicacao',
        icon: BellRing,
    },
    {
        title: 'Relatórios',
        href: '/relatorios',
        icon: BarChart3,
    },
    {
        title: 'Personalização',
        href: '/personalizacao',
        icon: Palette,
    },
    {
        title: 'Configurações',
        href: '/onboarding',
        icon: Settings2,
    },
    {
        title: 'Plano',
        href: '/plano',
        icon: BriefcaseBusiness,
        disabled: true,
        availabilityLabel: 'Etapa 7',
    },
];

export function AppSidebar() {
    const { permissions } = usePage().props;
    const visibleItems = mainNavItems.filter(
        (item) =>
            (item.href !== '/relatorios' || permissions.reports) &&
            (item.href !== '/personalizacao' || permissions.branding),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
