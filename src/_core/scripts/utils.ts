/* Packages */
import { utils as utilsShared, utilsBrowser as utilsBrowserShared } from '@displaycoffee/scripts/utils';

/* Utils from @displaycoffee/scripts, plus any custom scripts for this project */
export const utils: UtilsType = {
	...utilsShared,
};

export const utilsBrowser: UtilsBrowserType = {
	...utilsBrowserShared,
};
