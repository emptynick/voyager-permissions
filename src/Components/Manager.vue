<template>
    <div>
        <div class="card">
            <div class="flex">
                <Card class="w-full md:w-1/3" :class="assignRoles || assignUsers ? 'opacity-50' : null" :title="`Permissions (${permissions.total || 0})`">
                    <input type="text" class="input small w-full mb-3" v-model="parameters.permissions_query" placeholder="Search permissions">
                    <div v-if="permissions.data.length == 0 || loading" class="mb-2 text-center text-xl">
                        {{ loading ? 'Loading permissions' : 'No permissions match your queries' }}
                    </div>
                    <Card
                        class="cursor-pointer"
                        v-for="(permission, i) in permissions.data"
                        :key="`permission-${i}`"
                        :title="permission.name"
                        :titleSize="6"
                        :class="isSelected('permission', permission.id) ? 'border-accent-500' : null"
                        @click="select('permission', permission.id)"
                    >
                        <p v-html="permissionDescription(permission.name)"></p>
                        <small>Assigned to {{ permission.users.length }} users and {{ permission.roles.length }} roles.</small>
                    </Card>

                    <div class="flex w-full space-x-1">
                        <button class="button green justify-center w-full mb-2" @click="addPermission" v-tooltip="`Add a new permission`">
                            <Icon icon="plus" />
                            <span>Add</span>
                        </button>
                        <button class="button green justify-center w-full mb-2" @click="addPermissionBread" v-tooltip="`Add a new permission for a BREAD`">
                            <Icon icon="plus" />
                            <span>BREAD</span>
                        </button>
                        <button class="button green justify-center w-full mb-2" @click="addPermissionBuilder" v-tooltip="`Add a new permission for the BREAD builder`">
                            <Icon icon="plus" />
                            <span>Builder</span>
                        </button>
                    </div>

                    <Pagination v-model="permissions.current_page" :pageCount="permissions.last_page" />

                    <template #actions v-if="parameters.selected.type == 'permission'">
                        <button class="button small red" @click="remove()">Delete</button>
                        <button class="button small" @click="unselect">Unselect</button>
                        <button class="button small" @click="assign('users')">Users</button>
                        <button class="button small" @click="assign('roles')">Roles</button>
                    </template>
                    <template #actions v-else-if="assignPermissions">
                        <button class="button small green" @click="saveAssign()">Save ({{ this.assign_ids.length }})</button>
                        <button class="button small red" @click="cancelAssign()">Cancel</button>
                    </template>
                </Card>
                <Card class="w-full md:w-1/3" :class="assignPermissions || assignUsers ? 'opacity-50' : null" :title="`Roles (${roles.total || 0})`">
                    <input type="text" class="input small w-full mb-3" v-model="parameters.roles_query" placeholder="Search roles">
                    <div v-if="roles.data.length == 0 || loading" class="mb-2 text-center text-xl">
                        {{ loading ? 'Loading roles' : 'No roles match your queries' }}
                    </div>
                    <Card
                        class="cursor-pointer"
                        v-for="(role, i) in roles.data"
                        :key="`role-${i}`"
                        :title="role.name"
                        :titleSize="6"
                        :class="isSelected('role', role.id) ? 'border-accent-500' : null"
                        @click="select('role', role.id)"
                    >
                        Assigned to {{ role.users.length }} users. Has {{ role.permissions.length }} permissions
                    </Card>

                    <button class="button green justify-center w-full mb-2" @click="addRole">
                        <Icon icon="plus" />
                        <span>Add</span>
                    </button>

                    <Pagination v-model="roles.current_page" :pageCount="roles.last_page" />

                    <template #actions v-if="parameters.selected.type == 'role'">
                        <button class="button small red" @click="remove()">Delete</button>
                        <button class="button small" @click="unselect">Unselect</button>
                        <button class="button small" @click="assign('permissions')">Permissions</button>
                        <button class="button small" @click="assign('users')">Users</button>
                    </template>
                    <template #actions v-else-if="assignRoles">
                        <button class="button small green" @click="saveAssign()">Save ({{ this.assign_ids.length }})</button>
                        <button class="button small red" @click="cancelAssign()">Cancel</button>
                    </template>
                </Card>
                <Card class="w-full md:w-1/3" :class="assignRoles || assignPermissions ? 'opacity-50' : null" :title="`Users (${users.total || 0})`">
                    <input type="text" class="input small w-full mb-3" v-model="parameters.users_query" placeholder="Search users">
                    <div v-if="users.data.length == 0 || loading" class="mb-2 text-center text-xl">
                        {{ loading ? 'Loading users' : 'No users match your queries' }}
                    </div>
                    <Card
                        class="cursor-pointer"
                        v-for="(user, i) in users.data"
                        :key="`user-${i}`"
                        :title="user.name"
                        :titleSize="6"
                        @click="select('user', user.id)"
                        :class="isSelected('user', user.id) ? 'border-accent-500' : null"
                    >
                        {{ user.permissions.length }} permissions
                    </Card>

                    <Pagination v-model="users.current_page" :pageCount="users.last_page" />

                    <template #actions v-if="parameters.selected.type == 'user'">
                        <button class="button small" @click="unselect">Unselect</button>
                        <button class="button small" @click="assign('permissions')">Assign permissions</button>
                        <button class="button small" @click="assign('roles')">Assign roles</button>
                    </template>
                    <template #actions v-else-if="assignUsers">
                        <button class="button small green" @click="saveAssign()">Save ({{ this.assign_ids.length }})</button>
                        <button class="button small red" @click="cancelAssign()">Cancel</button>
                    </template>
                </Card>
            </div>
        </div>
        <Card title="Logging">
            <div class="text-center" v-if="!logging_enabled">
                Logging is currently disabled. Click <a href="#" @click.prevent="toggleLogging(true)">here</a> to enable it.
            </div>

            <div class="voyager-table">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Action</th>
                            <th>Guard</th>
                            <th>Requests</th>
                            <th>Last request</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(log, i) in logs" :key="`log-${i}`">
                            <td>{{ log.type }}</td>
                            <td>{{ log.action }}</td>
                            <td>{{ log.guard || 'None' }}</td>
                            <td>{{ log.count }}</td>
                            <td>{{ log.time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <template #actions v-if="logging_enabled">
                <button class="button blue" @click="toggleLogging(false)">Disable logging</button>
                <button class="button blue" @click="clearLogs()">Clear logs</button>
            </template>
        </Card>
    </div>
</template>

<script lang="ts">
import axios from 'axios';
import debounce from 'debounce';

export default {
    data() {
        return {
            permissions: {
                data: [],
                last_page: 1,
                current_page: 1,
            },
            roles: {
                data: [],
                last_page: 1,
                current_page: 1,
            },
            users: {
                data: [],
                last_page: 1,
                current_page: 1,
            },
            parameters: {
                per_page: 5,
                permissions_query: '',
                roles_query: '',
                users_query: '',
                selected: {
                    type: null,
                    id: null,
                }
            },
            loading: false,
            assign_to: null,
            assign_ids: [],
            logging_enabled: false,
            logs: [],
        }
    },
    computed: {
        assignPermissions() {
            return this.assign_to == 'permissions' && (this.parameters.selected.type == 'user' || this.parameters.selected.type == 'role');
        },
        assignRoles() {
            return this.assign_to == 'roles' && (this.parameters.selected.type == 'user' || this.parameters.selected.type == 'permission');
        },
        assignUsers() {
            return this.assign_to == 'users' && (this.parameters.selected.type == 'role' || this.parameters.selected.type == 'permission');
        },
        breads() {
            let breads = {};

            Object.values(this.$store.breads).forEach(bread => {
                breads[bread.table] = this.translate(bread.name_plural, true);
            });

            breads['*'] = 'All';

            return breads;
        },
        tables() {
            let tables = {};

            this.$store.tables.forEach(table => {
                tables[table] = table;
            });

            tables['*'] = 'All';

            return tables;
        }
    },
    methods: {
        load () {
            this.loading = true;
            axios.post(this.route('voyager.voyager-permissions'), {
                ...this.getParameters(),
                permissions_page: this.permissions.current_page,
                roles_page: this.roles.current_page,
                users_page: this.users.current_page,
            }).then((response) => {
                this.permissions = response.data.permissions;
                this.roles = response.data.roles;
                this.users = response.data.users;
                this.logging_enabled = response.data.logging;
            }).catch(() => {

            }).then(() => {
                this.loading = false;
            });
        },
        select (type, id) {
            if (this.assignPermissions || this.assignRoles || this.assignUsers) {
                if (this.assign_ids.includes(id)) {
                    this.assign_ids = this.assign_ids.removeAtIndex(this.assign_ids.indexOf(id));
                } else {
                    this.assign_ids.push(id);
                }

                return;
            }
            if (this.parameters.selected.type == type && this.parameters.selected.id == id) {
                this.unselect();
            } else {
                this.parameters.selected.type = type;
                this.parameters.selected.id = id;
            }

            if (type == 'permission') {
                this.users.current_page = 1;
            } else if (type == 'role') {
                this.permissions.current_page = 1;
                this.users.current_page = 1;
            } else if (type == 'user') {
                this.permissions.current_page = 1;
            }

            debounce(() => {
                this.load();
            }, 250);
        },
        unselect() {
            this.parameters.selected.type = null;
            this.parameters.selected.id = null;
        },
        isSelected (type, id) {
            if (this.assignPermissions) {
                return type == 'permission' && this.assign_ids.includes(id);
            } else if (this.assignRoles) {
                return type == 'role' && this.assign_ids.includes(id);
            } else if (this.assignUsers) {
                return type == 'user' && this.assign_ids.includes(id);
            }

            return this.parameters.selected.type == type && this.parameters.selected.id == id;
        },
        assign(type) {
            this.assign_to = type;
            this.assign_ids = [];
            // Reset query and page and prefill assign_ids
            if (this.assignPermissions) {
                this.permissions.current_page = 1;
                this.parameters.permissions_query = '';
            } else if (this.assignRoles) {
                this.roles.current_page = 1;
                this.parameters.roles_query = '';
            } else if (this.assignUsers) {
                this.users.current_page = 1;
                this.parameters.users_query = '';
            }

            if (this.parameters.selected.type == 'permission') {
                let permission = this.permissions.data.where('id', this.parameters.selected.id).first();
                if (this.assignRoles) {
                    this.assign_ids = permission.roles.pluck('id');
                } else if (this.assignUsers) {
                    this.assign_ids = permission.users.pluck('id');
                }
            } else if (this.parameters.selected.type == 'role') {
                let role = this.roles.data.where('id', this.parameters.selected.id).first();
                if (this.assignPermissions) {
                    this.assign_ids = role.permissions.pluck('id');
                } else if (this.assignUsers) {
                    this.assign_ids = role.users.pluck('id');
                }
            } else if (this.parameters.selected.type == 'user') {
                let user = this.users.data.where('id', this.parameters.selected.id).first();
                if (this.assignPermissions) {
                    this.assign_ids = user.permissions.pluck('id');
                } else if (this.assignRoles) {
                    this.assign_ids = user.roles.pluck('id');
                }
            }

            this.load();
        },
        cancelAssign() {
            this.assign_to = null;
            this.assign_ids = null;
            this.load();
        },
        saveAssign() {
            this.loading = true;
            axios.post(this.route('voyager.voyager-permissions-assign'), {
                selected_type: this.parameters.selected.type,
                selected_id: this.parameters.selected.id,
                assign_type: this.assign_to,
                assign_ids: this.assign_ids,
            }).then((response) => {
                new this.$notification('Successfully saved!').timeout().color('green').show();
            }).catch(() => {

            }).then(() => {
                this.loading = false;
                this.cancelAssign();
            });
        },
        savePermission(name: string) {
            axios.post(this.route('voyager.voyager-permissions.add-permission'), {
                name
            }).then((response) => {
                new this.$notification(`Permission with name "${name}" created!`).timeout().color('green').show();
                this.load();
            });
        },
        addPermission() {
            new this
            .$notification('Enter the name of the new permission')
            .timeout()
            .addButton({ key: true, value: this.__('voyager::generic.save'), color: 'accent'})
            .addButton({ key: false, value: this.__('voyager::generic.cancel'), color: 'red'})
            .prompt()
            .show()
            .then((result) => {
                if (result !== false && result !== '') {
                    this.savePermission(result);
                }
            });
        },
        addPermissionBread() {
            new this
            .$notification('Please select the ability and the BREAD')
            .timeout()
            .select({'browse': 'Browse', 'read': 'Read', 'edit': 'Edit', 'add': 'Add', 'delete': 'Delete', 'restore': 'Restore', 'force_delete': 'Force delete', '*': 'All' })
            .select(this.breads)
            .addButton({ key: true, value: this.__('voyager::generic.save'), color: 'accent'})
            .addButton({ key: false, value: this.__('voyager::generic.cancel'), color: 'red'})
            .confirm()
            .show()
            .then((result) => {
                if (result !== false && this.isArray(result) && result.length == 2) {
                    this.savePermission(`${result[0]} bread ${result[1]}`);
                }
            });
        },
        addPermissionBuilder() {
            new this
            .$notification('Please select the ability and the table')
            .timeout()
            .select({'browse': 'Browse', 'read': 'Read', 'edit': 'Edit', 'add': 'Add', 'delete': 'delete', 'restore': 'restore', 'force_delete': 'Force delete', '*': 'All'})
            .select(this.tables)
            .addButton({ key: true, value: this.__('voyager::generic.save'), color: 'accent'})
            .addButton({ key: false, value: this.__('voyager::generic.cancel'), color: 'red'})
            .confirm()
            .show()
            .then((result) => {
                if (result !== false && this.isArray(result) && result.length == 2) {
                    this.savePermission(`${result[0]} builder ${result[1]}`);
                }
            });
        },
        addRole() {
            new this
            .$notification('Enter the name of the new role')
            .timeout()
            .addButton({ key: true, value: this.__('voyager::generic.save'), color: 'accent'})
            .addButton({ key: false, value: this.__('voyager::generic.cancel'), color: 'red'})
            .prompt()
            .show()
            .then((result) => {
                if (result !== false && result !== '') {
                    axios.post(this.route('voyager.voyager-permissions.add-role'), {
                        name: result
                    }).then((response) => {
                        new this.$notification(`Role with name "${result}" created!`).timeout().color('green').show();
                        this.load();
                    });
                }
            });
        },
        toggleLogging(enabled) {
            axios.post(this.route('voyager.voyager-permissions.toggle-logging'), {
                enabled
            }).then((response) => {
                new this.$notification(enabled ? 'Logging was enabled!' : 'Logging was disabled!').timeout().color('green').show();
                this.loadLogs();
                this.logging_enabled = enabled;
            });
        },
        loadLogs(fromTimeout = false) {
            if (this.logging_enabled || !fromTimeout) {
                axios.post(this.route('voyager.voyager-permissions.get-logs')).then((response) => {
                    this.logs = response.data;
                });
            }
        },
        clearLogs() {
            axios.post(this.route('voyager.voyager-permissions.clear-logs')).then((response) => {
                this.logs = [];
            });
        },
        getParameters() {
            if (this.assignPermissions || this.assignRoles || this.assignUsers) {
                let p = JSON.parse(JSON.stringify(this.parameters));
                p.selected.type = null;
                p.selected.id = null;

                return p;
            }

            return this.parameters;
        },
        remove() {
            let name = '';
            if (this.parameters.selected.type == 'permission') {
                name = this.permissions.data.where('id', this.parameters.selected.id).first().name;
            } else if (this.parameters.selected.type == 'role') {
                name = this.roles.data.where('id', this.parameters.selected.id).first().name;
            }
            new this
            .$notification(`Are you sure you want to delete ${this.parameters.selected.type} "${name}"?`)
            .color('red')
            .timeout()
            .confirm()
            .show()
            .then((result) => {
                if (result == true) {
                    axios.post(this.route('voyager.voyager-permissions.remove'),
                        this.parameters.selected
                    ).then((response) => {
                        new this.$notification(`Deleted ${this.parameters.selected.type} "${name}"!`).timeout().color('green').show();
                        this.parameters.selected.type = null;
                        this.parameters.selected.id = null;
                        this.load();
                    });
                }
            });
        },
        permissionDescription(name) {
            if (name == 'browse permissions') {
                return 'Access the permission manager and show permissions';
            } else if (name == 'add permissions') {
                return 'Add new permissions into the database';
            } else if (name == 'delete permissions') {
                return 'Delete permissions from the database';
            } else if (name == 'assign permissions to roles') {
                return 'Assign permissions to roles';
            } else if (name == 'assign permissions to users') {
                return 'Assign permissions to users';
            } else if (name == 'browse permission-roles') {
                return 'Show roles in the permission manager';
            } else if (name == 'add permission-roles') {
                return 'Add new roles into the database';
            } else if (name == 'delete permission-roles') {
                return 'Delete roles from the database';
            } else if (name == 'assign roles to users') {
                return 'Assign roles to users';
            } else if (name == 'browse permission-users') {
                return 'Show users in the permission manager';
            } else if (name == 'browse voyager') {
                return 'Open and access Voyager';
            }
            let parts = name.split(' ');
            if (parts[2] !== '*') {
                let bread = Object.values(this.$store.breads).where('table', parts[2]).first();
                if (bread) {
                    parts[2] = `BREAD <code>${this.translate(bread.name_plural, true)}</code>`;
                } else {
                    parts[2] = `table <code>${parts[2]}</code>`;
                }
            }
            if (parts.length == 3 && parts[1] == 'bread') {
                if (parts[0] == '*' && parts[2] == '*') {
                    return 'Allows all actions on all BREADs';
                } else if (parts[0] == '*') {
                    return `Allows all actions on ${parts[2]}`;
                } else if (parts[2] == '*') {
                    return `Allows to ${parts[0]} all BREADs`;
                } else {
                    return `Allows to ${parts[0]} ${parts[2]}`;
                }
            } else if (parts.length == 3 && parts[1] == 'builder') {
                if (parts[0] == '*' && parts[2] == '*') {
                    return 'Allows all actions on all BREADs in the builder';
                } else if (parts[0] == '*') {
                    return `Allows all actions in the builder for ${parts[2]}`;
                } else if (parts[2] == '*') {
                    return `Allows to ${parts[0]} all BREADs in the builder`;
                } else {
                    return `Allows to ${parts[0]} ${parts[2]} in the builder`;
                }
            }

            return 'Custom permission';
        }
    },
    mounted() {
        this.$watch(() => this.users.current_page + this.permissions.current_page, debounce(() => {
            this.load();
        }, 250));
        this.$watch(() => this.parameters.per_page, debounce(() => {
            this.load();
        }, 250));

        this.$watch(() => this.parameters.permissions_query, debounce(() => {
            this.permissions.current_page = 1;
            this.load();
        }, 250));
        this.$watch(() => this.parameters.users_query, debounce(() => {
            this.users.current_page = 1;
            this.load();
        }, 250));

        this.$watch(() => this.parameters.selected, debounce(() => {
            this.load();
        }, 250), { deep: true });

        this.load();

        setInterval(() => {
            this.loadLogs(true);
        }, 5000);

        this.loadLogs();
    }
}
</script>