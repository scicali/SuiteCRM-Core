/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

import {Injectable} from '@angular/core';
import {CanActivate, Router, UrlTree} from '@angular/router';
import {Observable, of} from 'rxjs';
import {catchError, map, take} from 'rxjs/operators';
import {AuthService, SessionStatus} from './auth.service';
import {SystemConfigStore} from '../../store/system-config/system-config.store';
import {AppStateStore} from '../../store/app-state/app-state.store';
import {MessageService} from '../message/message.service';

@Injectable({
    providedIn: 'root'
})
export class MfaAuthGuard implements CanActivate {
    constructor(
        protected router: Router,
        private authService: AuthService,
        protected systemConfigStore: SystemConfigStore,
        protected appStateStore: AppStateStore,
        protected message: MessageService
    ) {
    }

    canActivate(): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

        const homePage = this.systemConfigStore.getHomePage();
        const homePageUrlTree: UrlTree = this.router.parseUrl(homePage);

        if (this.authService.isUserLoggedIn.value){
            return homePageUrlTree;
        }

        return this.authService.fetchSessionStatus()
            .pipe(
                take(1),
                map((user: SessionStatus) => {

                    if (user && user.appStatus.installed === false) {
                        return this.router.parseUrl('install');
                    }

                    if (user && user.active === true && user.userNeedFactorAuthentication === true && user.userFactorAuthenticated === true) {
                        this.authService.setUserNeedFactorAuthentication(user.userNeedFactorAuthentication);
                        this.authService.setUserFactorAuthenticated(user.userFactorAuthenticated);
                        return homePageUrlTree;
                    }

                    // Stay on mfa form
                    return true;
                }),
                catchError(() => of(true))
            );
    }
}
