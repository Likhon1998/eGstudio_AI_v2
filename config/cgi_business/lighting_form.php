<?php

$step08Hero = 'hero product';

return [
    'form_banner' => 'Fill in order: name your product → add its photo → write your marketing headline → describe how it is used → pick the room → set layout & light mood. Each step builds the next.',

    'step01_label' => 'Your product name',
    'step01_guide' => 'Type the exact name of what you sell — the same item shown in your photo (Step 02). Scenario: you sell a ceiling panel, write "18W Slim LED Panel Light". For a switch, write "2-Way Staircase Switch". If this does not match the photo, the poster may show the wrong product.',
    'step01_placeholder' => 'E.g. 18W LED Panel Light, 6A Wall Switch, Smart Touch Switch...',
    'step01_example' => 'Light: "18W Ceiling Panel" · Switch: "Modular 2-Way Switch" · Use the name your customer would recognize.',

    'step02_label' => 'Your product photo',
    'step02_guide' => 'Choose or upload the real photo of the product for this poster. The AI will copy this exact item — shape, color, and branding — in the final image. Scenario: upload a clear photo of your panel on white background, or pick one you saved before from the library.',

    'product_suggestions' => [
        ['icon' => '💡', 'label' => 'LED Bulb', 'value' => '9W Energy-Saving LED Bulb E27', 'category' => 'lights'],
        ['icon' => '🔲', 'label' => 'Panel Light', 'value' => '18W Slim LED Ceiling Panel Light', 'category' => 'lights'],
        ['icon' => '📏', 'label' => 'Tube Light', 'value' => '20W LED Tube Batten Light T5', 'category' => 'lights'],
        ['icon' => '🔆', 'label' => 'Downlight', 'value' => '7W Recessed LED Downlight', 'category' => 'lights'],
        ['icon' => '🎯', 'label' => 'Spotlight', 'value' => '12W Adjustable LED Spotlight', 'category' => 'lights'],
        ['icon' => '🔦', 'label' => 'Focus Light', 'value' => 'Adjustable LED Focus Light with Narrow Beam', 'category' => 'lights'],
        ['icon' => '🌈', 'label' => 'Smart Bulb', 'value' => 'Smart RGB WiFi LED Bulb', 'category' => 'lights'],
        ['icon' => '✨', 'label' => 'String Lights', 'value' => 'Outdoor Waterproof String Lights', 'category' => 'lights'],
        ['icon' => '🌦️', 'label' => 'Flood Light', 'value' => '50W Weatherproof LED Flood Light', 'category' => 'lights'],
        ['icon' => '🛋️', 'label' => 'Table Lamp', 'value' => 'Modern LED Table Lamp', 'category' => 'lights'],
        ['icon' => '💎', 'label' => 'Chandelier', 'value' => 'Decorative LED Chandelier', 'category' => 'lights'],
        ['icon' => '🏟️', 'label' => 'High Bay', 'value' => '150W Industrial LED High Bay Light', 'category' => 'lights'],
        ['icon' => '🌃', 'label' => 'Street Light', 'value' => 'LED Street Light Pole Fixture', 'category' => 'lights'],
        ['icon' => '🔘', 'label' => '1-Way Switch', 'value' => '6A Modular 1-Way Wall Switch', 'category' => 'switches'],
        ['icon' => '🔀', 'label' => '2-Way Switch', 'value' => '6A Modular 2-Way Staircase Switch', 'category' => 'switches'],
        ['icon' => '📱', 'label' => 'Smart WiFi', 'value' => 'Smart WiFi Touch Wall Switch 10A', 'category' => 'switches'],
        ['icon' => '🌗', 'label' => 'Dimmer', 'value' => 'LED Compatible Dimmer Switch', 'category' => 'switches'],
        ['icon' => '🌀', 'label' => 'Fan Regulator', 'value' => 'Ceiling Fan Speed Regulator Switch', 'category' => 'switches'],
        ['icon' => '👆', 'label' => 'Touch Panel', 'value' => 'Premium Glass Touch Panel Switch', 'category' => 'switches'],
        ['icon' => '🔔', 'label' => 'Bell Push', 'value' => 'Doorbell Bell Push Switch', 'category' => 'switches'],
        ['icon' => '👁️', 'label' => 'PIR Sensor', 'value' => 'PIR Motion Sensor Auto Switch', 'category' => 'switches'],
        ['icon' => '🔌', 'label' => 'Socket + Switch', 'value' => 'Combined 5A Socket and Switch Plate', 'category' => 'switches'],
        ['icon' => '⚡', 'label' => 'MCB Panel', 'value' => 'MCB Distribution Switch Panel', 'category' => 'switches'],
        ['icon' => '📡', 'label' => 'Remote Switch', 'value' => 'Wireless Remote Light Switch Kit', 'category' => 'switches'],
        ['icon' => '🏨', 'label' => 'Card Key', 'value' => 'Hotel Card Key Energy Saver Switch', 'category' => 'switches'],
        ['icon' => '🎛️', 'label' => 'Multi-Gang', 'value' => '4-Gang Modular Switch Plate', 'category' => 'switches'],
    ],

    'step03_label' => 'Marketing headline',
    'step03_guide' => 'The bold selling message on your advertisement — 2 to 3 short benefit phrases, not a long paragraph. Example: BRIGHT & UNIFORM · ENERGY SAVING · LONG LIFE. This headline sits in the poster layout (usually at the top). It is not printed on the product photo.',
    'step03_placeholder' => 'E.g. BRIGHT UNIFORM LIGHT · ENERGY SAVING · MODERN DESIGN',
    'step03_example' => 'Panel: BRIGHT UNIFORM LIGHT · ENERGY SAVING · Switch: SAFE SWITCHING · FIRE RETARDANT BODY',

    'marketing_chips' => [
        ['icon' => '⚡', 'label' => 'Energy Saving', 'value' => 'ENERGY SAVING PERFORMANCE', 'match' => 'ENERGY SAVING', 'category' => 'lights'],
        ['icon' => '🔆', 'label' => 'Bright & Uniform', 'value' => 'BRIGHT & UNIFORM ILLUMINATION', 'match' => 'BRIGHT', 'category' => 'lights'],
        ['icon' => '⏳', 'label' => 'Long Lifespan', 'value' => 'LONG LIFESPAN UP TO 25000 HOURS', 'match' => 'LIFESPAN', 'category' => 'lights'],
        ['icon' => '💎', 'label' => 'Modern Design', 'value' => 'MODERN & ELEGANT DESIGN', 'match' => 'ELEGANT', 'category' => 'lights'],
        ['icon' => '🔧', 'label' => 'Easy Install', 'value' => 'EASY INSTALLATION', 'match' => 'INSTALLATION', 'category' => 'lights'],
        ['icon' => '🍳', 'label' => 'Kitchen & Bath', 'value' => 'PERFECT FOR KITCHENS & WASHROOMS', 'match' => 'KITCHENS', 'category' => 'lights'],
        ['icon' => '🏠', 'label' => 'Home & Office', 'value' => 'IDEAL FOR HOMES & OFFICES', 'match' => 'HOMES', 'category' => 'lights'],
        ['icon' => '🔘', 'label' => 'Safe Switching', 'value' => 'SAFE & RELIABLE SWITCHING', 'match' => 'SAFE', 'category' => 'switches'],
        ['icon' => '📱', 'label' => 'Smart Control', 'value' => 'SMART APP & VOICE CONTROL', 'match' => 'SMART', 'category' => 'switches'],
        ['icon' => '🛡️', 'label' => 'Fire Retardant', 'value' => 'FIRE RETARDANT SWITCH BODY', 'match' => 'FIRE RETARDANT', 'category' => 'switches'],
        ['icon' => '👆', 'label' => 'Touch Control', 'value' => 'PREMIUM TOUCH CONTROL', 'match' => 'TOUCH', 'category' => 'switches'],
        ['icon' => '🌗', 'label' => 'Dimmer Ready', 'value' => 'SMOOTH DIMMING CONTROL', 'match' => 'DIMMING', 'category' => 'switches'],
    ],

    'usage_categories' => [
        'lights' => ['label' => 'Home & office lights', 'icon' => '💡'],
        'switches' => ['label' => 'Wall switches & controls', 'icon' => '🔘'],
    ],

    'step04_dropdown_label' => 'Browse common examples (optional)',
    'step04_helper' => 'Pick an example below, or type your own above — Step 05 will suggest matching rooms.',
    'step04_example' => 'Pick how it is fitted from the list below — Step 05 will suggest matching rooms. Example: ceiling panel → office or kitchen; wall switch → bedroom with lights ON.',

    'step05_label' => 'Which room or place',
    'step05_guide' => 'The background scene for your poster — where your product is shown in real life. Pick from the dropdown after Step 04, or type your own. Scenario: if Step 04 is a ceiling panel in an office, choose "Office Space". For a switch, show the switch on the wall and the room lights glowing ON.',
    'step05_placeholder' => 'E.g. Modern living room with warm ceiling light glowing',
    'step05_example' => 'Panel → office or kitchen · Pendant → dining room · Switch → bedroom with switch beside door and lights ON.',
    'step05_suggestions_label' => 'Suggested rooms & places',
    'step05_dropdown_placeholder' => 'Choose a room or place...',
    'step05_helper' => 'Fill in Step 04 first — matching room examples appear in the dropdown below.',

    'step06_label' => 'Video camera movement',
    'step06_guide' => 'Only used when you create a video ad later. Describe how the camera should move — slow zoom in, gentle pan left, etc. Scenario: "Slowly move closer to the wall switch while the room lights glow." For a still poster, a soft slow zoom works well.',
    'step06_placeholder' => 'E.g. Slow gentle zoom toward the product and glowing room',
    'step06_example' => 'Still poster: slow cinematic push-in · Video: smooth orbit around the product.',

    'step07_label' => 'Where product sits on poster',
    'step07_guide' => 'Where your product photo appears on the finished poster. Usually bottom-left or bottom-right so the top has space for your marketing headline. Example: product bottom-right, open area top-left for the headline.',
    'step07_placeholder' => 'E.g. Product on the right side, space on the left for headline text',
    'step07_example' => 'Most posters: product bottom-right or bottom-left · Headline sits at the top.',

    'step08_label' => 'Light mood & colors',
    'step08_guide' => 'The feeling of the lighting in your poster — warm and cozy, bright daylight, dark dramatic night, soft golden evening, etc. Describe mood and glow from your hero product only, not a different fixture. Scenario: "Deep dark room, soft glow around the product, warm amber light."',
    'step08_placeholder' => 'E.g. Deep cinematic darkness, soft ambient rim lighting, radiant hero product.',
    'step08_example' => 'Pick a mood chip or write your own — warm home, cool office, night demo, golden evening.',

    'lighting_style_chips' => [
        ['icon' => '🔥', 'label' => 'Warm White', 'value' => 'Warm amber wash, soft natural fill, radiant '.$step08Hero.'.'],
        ['icon' => '☀️', 'label' => 'Cool Daylight', 'value' => 'Cool daylight clarity, crisp clean contrast, luminous '.$step08Hero.'.'],
        ['icon' => '⚪', 'label' => 'Neutral White', 'value' => 'Balanced neutral white, even soft exposure, natural '.$step08Hero.' glow.'],
        ['icon' => '🔆', 'label' => 'Bright Uniform', 'value' => 'Bright even illumination, shadowless clarity, fully visible '.$step08Hero.'.'],
        ['icon' => '🌙', 'label' => 'Night Demo', 'value' => 'Deep cinematic darkness, soft ambient rim lighting, radiant '.$step08Hero.'.'],
        ['icon' => '🌗', 'label' => 'Dimmed Warm', 'value' => 'Intimate dimmed warmth, gentle soft falloff, glowing '.$step08Hero.'.'],
        ['icon' => '🏬', 'label' => 'Commercial', 'value' => 'Clean commercial brightness, professional neutral tone, sharp '.$step08Hero.'.'],
        ['icon' => '✨', 'label' => 'Accent Beam', 'value' => 'Focused accent beam, dramatic contrast, spotlit '.$step08Hero.'.'],
        ['icon' => '🏡', 'label' => 'Cozy Home', 'value' => 'Cozy warm interior glow, soft wall reflections, inviting '.$step08Hero.'.'],
        ['icon' => '🍽️', 'label' => 'Dining Glow', 'value' => 'Golden dining ambience, warm overhead wash, softly radiant '.$step08Hero.'.'],
        ['icon' => '🌦️', 'label' => 'Outdoor Night', 'value' => 'Cool night atmosphere, directional beam through darkness, luminous '.$step08Hero.'.'],
        ['icon' => '🌅', 'label' => 'Golden Evening', 'value' => 'Golden hour warmth, sunset rim light, beautifully radiant '.$step08Hero.'.'],
    ],
];
