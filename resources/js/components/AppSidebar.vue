<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, ChefHat, ClipboardList, ConciergeBell, CreditCard, LayoutGrid, Package, Tag, TrendingUp, Users, UtensilsCrossed, Wallet } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import { index as cashIndex } from '@/routes/cash';
import { index as categoriesIndex } from '@/routes/categories';
import { index as tablesIndex } from '@/routes/tables';
import { index as employeesIndex } from '@/routes/employees';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as salesIndex } from '@/routes/sales';
import { index as ordersIndex } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';
import { index as kitchenIndex } from '@/routes/kitchen';
import { index as waiterIndex } from '@/routes/waiter';
import { index as walletsIndex } from '@/actions/App/Http/Controllers/WalletController';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Órdenes',
        href: ordersIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Productos',
        href: productsIndex(),
        icon: Package,
    },
    {
        title: 'Categorías',
        href: categoriesIndex(),
        icon: Tag,
    },
    {
        title: 'Mesas',
        href: tablesIndex(),
        icon: UtensilsCrossed,
    },
    {
        title: 'Empleados',
        href: employeesIndex(),
        icon: Users,
    },
    {
        title: 'Proveedores',
        href: suppliersIndex(),
        icon: Building2,
    },
    {
        title: 'Caja',
        href: cashIndex(),
        icon: Wallet,
    },
    {
        title: 'Ventas del día',
        href: salesIndex(),
        icon: TrendingUp,
    },
    {
        title: 'Billeteras',
        href: walletsIndex().url,
        icon: CreditCard,
    },
    {
        title: 'Cocina',
        href: kitchenIndex(),
        icon: ChefHat,
    },
    {
        title: 'Modo mesero',
        href: waiterIndex(),
        icon: ConciergeBell,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
