import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {TableheaderUiComponent} from './table-header.component';
import {PaginationUiModule} from '@components/pagination/pagination.module';
import {BulkactionmenuUiModule} from '@components/bulk-action-menu/bulk-action-menu.module';
import {AngularSvgIconModule} from 'angular-svg-icon';
import {HttpClientTestingModule} from '@angular/common/http/testing';
import {ApolloTestingModule} from 'apollo-angular/testing';
import {ImageModule} from '@components/image/image.module';
import {ThemeImagesFacade} from '@base/facades/theme-images/theme-images.facade';
import {of} from 'rxjs';
import {themeImagesMockData} from '@base/facades/theme-images/theme-images.facade.spec.mock';
import {take} from 'rxjs/operators';

describe('TableheaderUiComponent', () => {
    let component: TableheaderUiComponent;
    let fixture: ComponentFixture<TableheaderUiComponent>;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            imports: [
                PaginationUiModule,
                BulkactionmenuUiModule,
                AngularSvgIconModule,
                HttpClientTestingModule,
                ApolloTestingModule,
                ImageModule
            ],
            declarations: [TableheaderUiComponent],
            providers: [
                {
                    provide: ThemeImagesFacade, useValue: {
                        images$: of(themeImagesMockData).pipe(take(1))
                    }
                },
            ],
        })
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(TableheaderUiComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});