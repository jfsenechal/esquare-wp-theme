import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

// Dynamic block: render.php owns the front-end markup.
registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null,
});
