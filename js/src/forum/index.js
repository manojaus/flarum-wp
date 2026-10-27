import app from 'flarum/forum/app';

import {ID} from '../config';
import {intercept} from './intercept';

app.initializers.add(ID, () => {
	intercept();
});
