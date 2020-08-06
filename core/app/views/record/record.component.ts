import {Component, OnDestroy, OnInit} from '@angular/core';
import {AppStateStore} from '@store/app-state/app-state.store';
import {Observable, Subscription} from 'rxjs';
import {RecordViewModel, RecordViewStore} from '@store/record-view/record-view.store';
import {ActivatedRoute} from '@angular/router';

@Component({
    selector: 'scrm-record',
    templateUrl: './record.component.html',
    styleUrls: [],
    providers: [RecordViewStore]
})
export class RecordComponent implements OnInit, OnDestroy {
    recordSub: Subscription;
    vm$: Observable<RecordViewModel> = null;

    constructor(protected appState: AppStateStore, protected recordStore: RecordViewStore, private route: ActivatedRoute) {
    }

    ngOnInit(): void {
        this.recordSub = this.recordStore.init(this.appState.getModule(), this.route.snapshot.params.record).subscribe();
        this.vm$ = this.recordStore.vm$;
    }

    ngOnDestroy(): void {
        if (this.recordSub) {
            this.recordSub.unsubscribe();
        }

        this.recordStore.destroy();
    }
}
