import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Processing } from './processing';

describe('Processing', () => {
    let component: Processing;
    let fixture: ComponentFixture<Processing>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Processing],
        }).compileComponents();

        fixture = TestBed.createComponent(Processing);
        component = fixture.componentInstance;
        await fixture.whenStable();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
