import Vue from 'vue';
import VueRouter from 'vue-router';
const RouterEntry = () => import ('~/views/RouterEntry');
const LoginIndex = () => import ('~/views/LoginIndex');
const ApplicationEntry = () => import ('~/views/ApplicationEntry');
const PublicEntry = () => import ('~/views/PublicEntry');
const DashboardIndex = () => import ('~/views/DashboardIndex');
const UserIndex = () => import ('~/views/users/UserIndex');
const UserCreateEdit = () => import ('~/views/users/UserCreateEdit');
const StoreIndex = () => import ('~/views/stores/StoreIndex');
const StoreImport = () => import ('~/views/stores/StoreImport');


Vue.use(VueRouter);
const router = new VueRouter({
    mode: 'history',
    linkExactActiveClass: 'is-active',
    routes: [
        {
            path: '/',
            component: RouterEntry,
            name: 'Entry',
            redirect: 'dashboard',
            meta: {
                title: 'Developer Test',
                header: 'Developer Test'
            },
            children: [
                {
                    path: '',
                    component: PublicEntry,
                    name: 'Public Entry',
                    redirect: 'login',
                    children: [
                        {
                            path: 'login',
                            component: LoginIndex,
                            name: 'Login',
                            meta: {
                                title: 'Social Places | Login'
                            }
                        }
                    ]
                },
                {
                    path: '',
                    component: ApplicationEntry,
                    name: 'Application Entry',
                    redirect: 'dashboard',
                    children: [
                        {
                            path: 'dashboard',
                            name: 'Dashboard',
                            component: DashboardIndex,
                            meta: {
                                title: 'Dashboard',
                                header: 'Dashboard'
                            }
                        },
                        {
                            path: 'users',
                            name: 'Users',
                            redirect: 'Users Index',
                            component: RouterEntry,
                            meta: {
                                title: 'Users | Social Places',
                                header: 'Users',
                            },
                            children: [
                                {
                                    path: '',
                                    name: 'Users Index',
                                    component: UserIndex,
                                    meta: {
                                        title: 'Users | Social Places',
                                        header: 'Users',
                                    },
                                },
                                {
                                    path: 'create',
                                    name: 'Users Create',
                                    component: UserCreateEdit,
                                    meta: {
                                        title: 'Users Create | Social Places',
                                        header: 'Users Create',
                                    },
                                },
                                {
                                    path: 'edit/:id',
                                    name: 'Users Edit',
                                    component: UserCreateEdit,
                                    meta: {
                                        title: 'Users Edit | Social Places',
                                        header: 'Users Edit',
                                    },
                                }
                            ]
                        },
                        {
                            path: 'stores',
                            name: 'Stores',
                            redirect: 'Stores Index',
                            component: RouterEntry,
                            meta: {
                                title: 'Stores | Social Places',
                                header: 'Stores',
                            },
                            children: [
                                {
                                    path: '',
                                    name: 'Stores Index',
                                    component: StoreIndex,
                                    meta: {
                                        title: 'Stores | Social Places',
                                        header: 'Stores',
                                    },
                                },
                                {
                                    path: 'import',
                                    name: 'Stores Import',
                                    component: StoreImport,
                                    meta: {
                                        title: 'Stores Import | Social Places',
                                        header: 'Stores Import',
                                    },
                                },
                            ]
                        },


                        {path: '/404', name: 'Page Not Found'},
                    ]
                }
            ]
        },
        {path: '*', redirect: '/404'},
    ],
    scrollBehavior() { // ensures page is scrolled to top on navigation
        return {x: 0, y: 0};
    },
});

export default router;
