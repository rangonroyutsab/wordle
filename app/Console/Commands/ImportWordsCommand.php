<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Word;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Command to import Wordle words from a word list.
 */
class ImportWordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wordle:import-words 
                            {--file= : Path to a local file containing words}
                            {--solutions-only : Only import solution words}
                            {--clear : Clear existing words before import}';

    /**
     * The console command description.
     */
    protected $description = 'Import Wordle words from a file or default word list';

    /**
     * Default Wordle solution words (official NYT Wordle list subset).
     */
    private array $solutionWords = [
        'ABACK', 'ABASE', 'ABATE', 'ABBEY', 'ABBOT', 'ABHOR', 'ABIDE', 'ABLED', 'ABODE', 'ABORT',
        'ABOUT', 'ABOVE', 'ABUSE', 'ABYSS', 'ACORN', 'ACRID', 'ACTOR', 'ACUTE', 'ADAGE', 'ADAPT',
        'ADEPT', 'ADMIN', 'ADMIT', 'ADOBE', 'ADOPT', 'ADORE', 'ADORN', 'ADULT', 'AFFIX', 'AFOOT',
        'AFOUL', 'AFTER', 'AGAIN', 'AGAPE', 'AGATE', 'AGENT', 'AGILE', 'AGING', 'AGONY', 'AGREE',
        'AHEAD', 'AIDER', 'AISLE', 'ALARM', 'ALBUM', 'ALERT', 'ALIBI', 'ALIEN', 'ALIGN', 'ALIKE',
        'ALIVE', 'ALLAY', 'ALLEY', 'ALLOT', 'ALLOW', 'ALLOY', 'ALOFT', 'ALONE', 'ALONG', 'ALOOF',
        'ALOUD', 'ALPHA', 'ALTAR', 'ALTER', 'AMASS', 'AMAZE', 'AMBER', 'AMBLE', 'AMEND', 'AMISS',
        'AMITY', 'AMONG', 'AMPLE', 'AMUSE', 'ANGEL', 'ANGER', 'ANGLE', 'ANGRY', 'ANGST', 'ANIME',
        'ANKLE', 'ANNEX', 'ANNOY', 'ANNUL', 'ANODE', 'ANTIC', 'ANVIL', 'AORTA', 'APART', 'APHID',
        'APING', 'APNEA', 'APPLE', 'APPLY', 'APRON', 'APTLY', 'ARBOR', 'ARDOR', 'ARENA', 'ARGUE',
        'ARISE', 'ARMOR', 'AROMA', 'AROSE', 'ARRAY', 'ARROW', 'ARSON', 'ARTSY', 'ASCOT', 'ASHEN',
        'ASIDE', 'ASKEW', 'ASSAY', 'ASSET', 'ATOLL', 'ATOM', 'ATONE', 'ATTIC', 'AUDIO', 'AUDIT',
        'AUGUR', 'AUNTY', 'AVAIL', 'AVERT', 'AVIAN', 'AVOID', 'AWAIT', 'AWAKE', 'AWARD', 'AWARE',
        'AWASH', 'AWFUL', 'AWOKE', 'AXIAL', 'AXIOM', 'AZURE', 'BACON', 'BADGE', 'BADLY', 'BAGEL',
        'BAGGY', 'BAKER', 'BALER', 'BALMY', 'BANAL', 'BANJO', 'BARGE', 'BARON', 'BASAL', 'BASIC',
        'BASIL', 'BASIN', 'BASIS', 'BASTE', 'BATCH', 'BATHE', 'BATON', 'BATTY', 'BAWDY', 'BAYOU',
        'BEACH', 'BEADY', 'BEARD', 'BEAST', 'BEECH', 'BEEFY', 'BEFIT', 'BEGAN', 'BEGAT', 'BEGET',
        'BEGIN', 'BEGUN', 'BEING', 'BELCH', 'BELIE', 'BELLE', 'BELLY', 'BELOW', 'BENCH', 'BERET',
        'BERRY', 'BERTH', 'BESET', 'BETEL', 'BEVEL', 'BEWARE', 'BIBLE', 'BICEP', 'BIDDY', 'BIGHT',
        'BILLY', 'BINGE', 'BINGO', 'BIOME', 'BIRCH', 'BIRTH', 'BISON', 'BITTY', 'BLACK', 'BLADE',
        'BLAME', 'BLAND', 'BLANK', 'BLARE', 'BLAST', 'BLAZE', 'BLEAK', 'BLEAT', 'BLEED', 'BLEEP',
        'BLEND', 'BLESS', 'BLIMP', 'BLIND', 'BLINI', 'BLINK', 'BLISS', 'BLITZ', 'BLOAT', 'BLOCK',
        'BLOKE', 'BLOND', 'BLOOD', 'BLOOM', 'BLOWN', 'BLUER', 'BLUFF', 'BLUNT', 'BLURB', 'BLURT',
        'BLUSH', 'BOARD', 'BOAST', 'BOBBY', 'BODED', 'BOGEY', 'BOGGY', 'BONUS', 'BOOBY', 'BOOST',
        'BOOTH', 'BOOTY', 'BOOZE', 'BOOZY', 'BORAX', 'BORNE', 'BOSOM', 'BOSSY', 'BOTCH', 'BOUGH',
        'BOULE', 'BOUND', 'BOWEL', 'BOXER', 'BRACE', 'BRAID', 'BRAIN', 'BRAKE', 'BRAND', 'BRASH',
        'BRASS', 'BRAVE', 'BRAVO', 'BRAWL', 'BRAWN', 'BREAD', 'BREAK', 'BREED', 'BRIAR', 'BRIBE',
        'BRICK', 'BRIDE', 'BRIEF', 'BRIER', 'BRINE', 'BRING', 'BRINK', 'BRINY', 'BRISK', 'BROAD',
        'BROIL', 'BROKE', 'BROOD', 'BROOK', 'BROOM', 'BROTH', 'BROWN', 'BRUNT', 'BRUSH', 'BRUTE',
        'BUDDY', 'BUDGE', 'BUGGY', 'BUGLE', 'BUILD', 'BUILT', 'BULGE', 'BULKY', 'BULLY', 'BUNCH',
        'BUNNY', 'BURLY', 'BURNT', 'BURST', 'BUSED', 'BUSHY', 'BUTCH', 'BUTTE', 'BUXOM', 'BUYER',
        'BYLAW', 'CABAL', 'CABBY', 'CABIN', 'CABLE', 'CACAO', 'CACHE', 'CACTI', 'CADDY', 'CADET',
        'CAFÉ', 'CAGE', 'CAGEY', 'CAIRN', 'CAMEL', 'CAMEO', 'CANAL', 'CANDY', 'CANNY', 'CANOE',
        'CANON', 'CAPER', 'CAPUT', 'CARAT', 'CARGO', 'CAROL', 'CARRY', 'CARVE', 'CASTE', 'CATCH',
        'CATER', 'CATTY', 'CAULK', 'CAUSE', 'CAVIL', 'CEASE', 'CEDAR', 'CELLO', 'CHAFE', 'CHAFF',
        'CHAIN', 'CHAIR', 'CHALK', 'CHAMP', 'CHANT', 'CHAOS', 'CHARD', 'CHARM', 'CHART', 'CHASE',
        'CHASM', 'CHEAP', 'CHEAT', 'CHECK', 'CHEEK', 'CHEER', 'CHESS', 'CHEST', 'CHICK', 'CHIDE',
        'CHIEF', 'CHILD', 'CHILL', 'CHIMP', 'CHINA', 'CHIRP', 'CHOCK', 'CHOIR', 'CHOKE', 'CHORD',
        'CHORE', 'CHOSE', 'CHUNK', 'CHURN', 'CHUTE', 'CIDER', 'CIGAR', 'CINCH', 'CIRCA', 'CIVIC',
        'CIVIL', 'CLACK', 'CLAIM', 'CLAMP', 'CLANG', 'CLANK', 'CLASH', 'CLASP', 'CLASS', 'CLEAN',
        'CLEAR', 'CLEAT', 'CLERK', 'CLICK', 'CLIFF', 'CLIMB', 'CLING', 'CLOAK', 'CLOCK', 'CLONE',
        'CLOSE', 'CLOTH', 'CLOUD', 'CLOUT', 'CLOVE', 'CLOWN', 'CLUB', 'CLUCK', 'CLUED', 'CLUMP',
        'CLUNG', 'COACH', 'COAST', 'COBRA', 'COCOA', 'COLON', 'COLOR', 'COMET', 'COMFY', 'COMIC',
        'COMMA', 'CONCH', 'CONDO', 'CONIC', 'COPSE', 'CORAL', 'CORER', 'CORNY', 'COUCH', 'COUGH',
        'COULD', 'COUNT', 'COUPE', 'COURT', 'COVEN', 'COVER', 'COVET', 'COWER', 'CRACK', 'CRAFT',
        'CRAMP', 'CRANE', 'CRANK', 'CRASH', 'CRASS', 'CRATE', 'CRAVE', 'CRAWL', 'CRAZE', 'CRAZY',
        'CREAK', 'CREAM', 'CREDO', 'CREED', 'CREEK', 'CREEP', 'CREME', 'CREPE', 'CREPT', 'CRESS',
        'CREST', 'CRICK', 'CRIED', 'CRIER', 'CRIME', 'CRIMP', 'CRISP', 'CROAK', 'CROCK', 'CRONE',
        'CRONY', 'CROOK', 'CROSS', 'CROUP', 'CROWD', 'CROWN', 'CRUDE', 'CRUEL', 'CRUSH', 'CRUST',
        'CRYPT', 'CUBIC', 'CUMIN', 'CUPID', 'CURLY', 'CURRY', 'CURSE', 'CURVE', 'CURVY', 'CUTIE',
        'CYBER', 'CYCLE', 'CYNIC', 'DADDY', 'DAILY', 'DAIRY', 'DAISY', 'DALLY', 'DANCE', 'DANDY',
        'DATUM', 'DAUNT', 'DEALT', 'DEATH', 'DEBAR', 'DEBIT', 'DEBUG', 'DEBUT', 'DECAL', 'DECAY',
        'DECOR', 'DECOY', 'DECRY', 'DEFER', 'DEITY', 'DELAY', 'DELTA', 'DELVE', 'DEMON', 'DEMUR',
        'DENIM', 'DENSE', 'DEPOT', 'DEPTH', 'DERBY', 'DETER', 'DETOX', 'DEUCE', 'DEVIL', 'DIARY',
        'DICEY', 'DIGIT', 'DILLY', 'DIMLY', 'DINER', 'DINGO', 'DINGY', 'DIODE', 'DIRGE', 'DIRTY',
        'DISCO', 'DITCH', 'DITTO', 'DITTY', 'DIVER', 'DIZZY', 'DODGE', 'DODGY', 'DOGMA', 'DOING',
        'DOLLY', 'DONOR', 'DONUT', 'DOPEY', 'DOUBT', 'DOUGH', 'DOUSE', 'DOWDY', 'DOWEL', 'DOWNY',
        'DOWRY', 'DOZEN', 'DRAFT', 'DRAIN', 'DRAKE', 'DRAMA', 'DRANK', 'DRAPE', 'DRAWL', 'DRAWN',
        'DREAD', 'DREAM', 'DRESS', 'DRIED', 'DRIER', 'DRIFT', 'DRILL', 'DRINK', 'DRIVE', 'DROIT',
        'DROLL', 'DRONE', 'DROOL', 'DROOP', 'DROSS', 'DROVE', 'DROWN', 'DRUGS', 'DRUNK', 'DRYER',
        'DRYLY', 'DUCHY', 'DULLY', 'DUMBO', 'DUMMY', 'DUMPY', 'DUNCE', 'DUNE', 'DUPLE', 'DURAL',
        'DUSKY', 'DUSTY', 'DUTCH', 'DUVET', 'DWARF', 'DWELL', 'DWELT', 'DYING', 'EAGER', 'EAGLE',
        'EARLY', 'EARTH', 'EASEL', 'EATEN', 'EATER', 'EAVES', 'EBONY', 'ECLAT', 'EDICT', 'EDIFY',
        'EERIE', 'EGRET', 'EIGHT', 'EJECT', 'EKING', 'ELATE', 'ELBOW', 'ELDER', 'ELECT', 'ELEGY',
        'ELFIN', 'ELITE', 'ELOPE', 'ELUDE', 'EMAIL', 'EMBED', 'EMBER', 'EMCEE', 'EMPTY', 'ENACT',
        'ENDOW', 'ENEMA', 'ENEMY', 'ENJOY', 'ENNUI', 'ENSUE', 'ENTER', 'ENTRY', 'ENVOY', 'EPOCH',
        'EPOXY', 'EQUAL', 'EQUIP', 'ERASE', 'ERECT', 'ERODE', 'ERROR', 'ERUPT', 'ESSAY', 'ETHER',
        'ETHIC', 'ETHOS', 'EVADE', 'EVENT', 'EVERY', 'EVICT', 'EVOKE', 'EXACT', 'EXALT', 'EXCEL',
        'EXERT', 'EXILE', 'EXIST', 'EXPAT', 'EXPEL', 'EXTOL', 'EXTRA', 'EXUDE', 'EXULT', 'EYING',
        'FABLE', 'FACET', 'FAINT', 'FAIRY', 'FAITH', 'FALSE', 'FAMED', 'FANCY', 'FANNY', 'FARCE',
        'FATAL', 'FATTY', 'FAULT', 'FAUNA', 'FAVOR', 'FEAST', 'FECAL', 'FEIGN', 'FELLA', 'FELON',
        'FEMUR', 'FENCE', 'FERAL', 'FERRY', 'FETAL', 'FETCH', 'FETID', 'FETUS', 'FEUD', 'FEVER',
        'FIBER', 'FICUS', 'FIELD', 'FIEND', 'FIERY', 'FIFTH', 'FIFTY', 'FIGHT', 'FILER', 'FILET',
        'FILMY', 'FILTH', 'FINAL', 'FINCH', 'FINER', 'FIRST', 'FISHY', 'FIXER', 'FIZZY', 'FJORD',
        'FLACK', 'FLAIL', 'FLAIR', 'FLAKE', 'FLAKY', 'FLAME', 'FLANK', 'FLARE', 'FLASH', 'FLASK',
        'FLESH', 'FLICK', 'FLIER', 'FLING', 'FLINT', 'FLOAT', 'FLOCK', 'FLOOD', 'FLOOR', 'FLORA',
        'FLOSS', 'FLOUR', 'FLOUT', 'FLOWN', 'FLUID', 'FLUKE', 'FLUNG', 'FLUNK', 'FLUSH', 'FLUTE',
        'FLYBY', 'FLYER', 'FOAMY', 'FOCAL', 'FOCUS', 'FOGGY', 'FOIST', 'FOLIO', 'FOLLY', 'FORAY',
        'FORCE', 'FORGE', 'FORGO', 'FORTE', 'FORTH', 'FORTY', 'FORUM', 'FOSSA', 'FOUND', 'FOYER',
        'FRAIL', 'FRAME', 'FRANK', 'FRAUD', 'FREAK', 'FREED', 'FRESH', 'FRIAR', 'FRIED', 'FRILL',
        'FRISK', 'FRITZ', 'FRIZZ', 'FROCK', 'FROND', 'FRONT', 'FROST', 'FROTH', 'FROWN', 'FROZE',
        'FRUIT', 'FUDGE', 'FUGUE', 'FULLY', 'FUNGI', 'FUNKY', 'FUNNY', 'FURRY', 'FUSSY', 'FUZZY',
        'GAFFE', 'GAILY', 'GAMER', 'GAMMA', 'GAMUT', 'GASSY', 'GAUGE', 'GAUNT', 'GAUZE', 'GAUZY',
        'GAVEL', 'GAWKY', 'GAYER', 'GAYLY', 'GAZER', 'GECKO', 'GEEKY', 'GEESE', 'GENIE', 'GENRE',
        'GHOST', 'GIANT', 'GIDDY', 'GILD', 'GIVEN', 'GIVER', 'GLADE', 'GLAND', 'GLARE', 'GLASS',
        'GLAZE', 'GLEAM', 'GLEAN', 'GLEE', 'GLIDE', 'GLINT', 'GLOAT', 'GLOBE', 'GLOOM', 'GLORY',
        'GLOSS', 'GLOVE', 'GLYPH', 'GNARLY', 'GNASH', 'GNOME', 'GODLY', 'GOING', 'GOLEM', 'GOLLY',
        'GONAD', 'GONER', 'GOODY', 'GOOEY', 'GOOFY', 'GOOSE', 'GORGE', 'GOUGE', 'GOURD', 'GRACE',
        'GRADE', 'GRAFT', 'GRAIL', 'GRAIN', 'GRAND', 'GRANT', 'GRAPE', 'GRAPH', 'GRASP', 'GRASS',
        'GRATE', 'GRAVE', 'GRAVY', 'GRAZE', 'GREAT', 'GREED', 'GREEK', 'GREEN', 'GREET', 'GRIEF',
        'GRILL', 'GRIME', 'GRIMY', 'GRIND', 'GRIPE', 'GROAN', 'GROOM', 'GROPE', 'GROSS', 'GROUP',
        'GROUT', 'GROVE', 'GROWL', 'GROWN', 'GRUEL', 'GRUFF', 'GRUMP', 'GRUNT', 'GUARD', 'GUAVA',
        'GUESS', 'GUEST', 'GUIDE', 'GUILD', 'GUILT', 'GUISE', 'GULCH', 'GULLY', 'GUMMY', 'GUPPY',
        'GUSTO', 'GUSTY', 'GYPSY', 'HABIT', 'HAIKU', 'HALLO', 'HALVE', 'HANDY', 'HANKS', 'HAPPY',
        'HARDY', 'HAREM', 'HARPY', 'HARRY', 'HARSH', 'HASTE', 'HASTY', 'HATCH', 'HATER', 'HAUNT',
        'HAUTE', 'HAVEN', 'HAVOC', 'HAZEL', 'HEADY', 'HEARD', 'HEART', 'HEATH', 'HEAVE', 'HEAVY',
        'HEDGE', 'HEEDY', 'HEIST', 'HELIX', 'HELLO', 'HENCE', 'HENNA', 'HERBS', 'HERON', 'HILLY',
        'HINGE', 'HIPPO', 'HIPPY', 'HITCH', 'HOARD', 'HOBBY', 'HOIST', 'HOLLY', 'HOMER', 'HONEY',
        'HONOR', 'HORDE', 'HORNY', 'HORSE', 'HOTEL', 'HOTLY', 'HOUND', 'HOUSE', 'HOVEL', 'HOVER',
        'HOWDY', 'HUMAN', 'HUMID', 'HUMOR', 'HUMPH', 'HUMUS', 'HUNCH', 'HUNKY', 'HURRY', 'HUSKY',
        'HUSSY', 'HUTCH', 'HYDRA', 'HYENA', 'HYMEN', 'HYPER', 'ICILY', 'ICING', 'IDEAL', 'IDIOM',
        'IDIOT', 'IDLER', 'IDYLL', 'IGLOO', 'IMAGE', 'IMBUE', 'IMPEL', 'IMPLY', 'INANE', 'INBOX',
        'INCUR', 'INDEX', 'INDIE', 'INEPT', 'INERT', 'INFER', 'INGOT', 'INLAY', 'INLET', 'INNER',
        'INPUT', 'INTER', 'INTRO', 'IONIC', 'IRATE', 'IRONY', 'ISLET', 'ISSUE', 'ITCHY', 'IVORY',
        'JAUNT', 'JAZZY', 'JEANS', 'JELLY', 'JERKY', 'JESUS', 'JEWEL', 'JIFFY', 'JOINT', 'JOKER',
        'JOLLY', 'JOUST', 'JUDGE', 'JUICE', 'JUICY', 'JUMBO', 'JUMPY', 'JUNCO', 'JUNIOR', 'JUROR',
        'KAPPA', 'KARMA', 'KAYAK', 'KEBAB', 'KHAKI', 'KIDDO', 'KINKY', 'KIOSK', 'KITTY', 'KNACK',
        'KNEAD', 'KNEED', 'KNEEL', 'KNELT', 'KNIFE', 'KNOCK', 'KNOLL', 'KNOWN', 'KOALA', 'KRILL',
        'LABEL', 'LABOR', 'LADEN', 'LADLE', 'LAGER', 'LANCE', 'LANKY', 'LAPEL', 'LAPSE', 'LARGE',
        'LARVA', 'LASSO', 'LATCH', 'LATER', 'LATHE', 'LATTE', 'LAUGH', 'LAYER', 'LEACH', 'LEAFY',
        'LEAKY', 'LEANT', 'LEAPT', 'LEARN', 'LEASE', 'LEASH', 'LEAST', 'LEAVE', 'LEDGE', 'LEECH',
        'LEERY', 'LEFTY', 'LEGAL', 'LEGGY', 'LEMON', 'LEMUR', 'LEPER', 'LEVEL', 'LEVER', 'LIBEL',
        'LIEGE', 'LIGHT', 'LIKEN', 'LILAC', 'LIMBO', 'LIMIT', 'LIMPY', 'LINER', 'LINEN', 'LINER',
        'LINGO', 'LIPID', 'LITHE', 'LIVER', 'LIVID', 'LLAMA', 'LOAMY', 'LOATH', 'LOBBY', 'LOCAL',
        'LOCUS', 'LODGE', 'LOFTY', 'LOGIC', 'LOGIN', 'LOINS', 'LONER', 'LOOSE', 'LORRY', 'LOSER',
        'LOTTO', 'LOTUS', 'LOUSE', 'LOUSY', 'LOVER', 'LOWER', 'LOWLY', 'LOYAL', 'LUCID', 'LUCKY',
        'LUMEN', 'LUMPY', 'LUNAR', 'LUNCH', 'LUNGE', 'LUPUS', 'LURCH', 'LURID', 'LUSTY', 'LYING',
        'LYMPH', 'LYNCH', 'LYRIC', 'MACAW', 'MACHO', 'MACRO', 'MADAM', 'MADLY', 'MAFIA', 'MAGIC',
        'MAGMA', 'MAIZE', 'MAJOR', 'MAKER', 'MAMBO', 'MAMMA', 'MAMMY', 'MANGA', 'MANGE', 'MANGO',
        'MANGY', 'MANIA', 'MANIC', 'MANLY', 'MANOR', 'MAPLE', 'MARCH', 'MARRY', 'MARSH', 'MASON',
        'MASSE', 'MATCH', 'MATEY', 'MAUVE', 'MAXIM', 'MAYBE', 'MAYOR', 'MEALY', 'MEANT', 'MEATY',
        'MECCA', 'MEDAL', 'MEDIA', 'MEDIC', 'MELEE', 'MELON', 'MERCY', 'MERGE', 'MERIT', 'MERRY',
        'METAL', 'METER', 'METRO', 'MICRO', 'MIDGE', 'MIDST', 'MIGHT', 'MILKY', 'MIMIC', 'MINCE',
        'MINER', 'MINOR', 'MINUS', 'MIRTH', 'MISER', 'MISSY', 'MOCHA', 'MODAL', 'MODEL', 'MODEM',
        'MOIST', 'MOLAR', 'MOLDY', 'MONEY', 'MONTH', 'MOODY', 'MOOSE', 'MORAL', 'MORON', 'MORPH',
        'MOSSY', 'MOTEL', 'MOTIF', 'MOTOR', 'MOTTO', 'MOUND', 'MOUNT', 'MOURN', 'MOUSE', 'MOUTH',
        'MOVER', 'MOVIE', 'MOWER', 'MUCUS', 'MUDDY', 'MULCH', 'MUMMY', 'MUNCH', 'MURAL', 'MURKY',
        'MUSHY', 'MUSIC', 'MUSKY', 'MUSTY', 'MYRRH', 'NACHO', 'NAIVE', 'NANNY', 'NASAL', 'NASTY',
        'NATAL', 'NAVAL', 'NAVEL', 'NEEDY', 'NEIGH', 'NERDY', 'NERVE', 'NEVER', 'NEWER', 'NEWLY',
        'NICER', 'NICHE', 'NIECE', 'NIGHT', 'NINJA', 'NINNY', 'NINTH', 'NOBLE', 'NOBLY', 'NOISE',
        'NOISY', 'NOMAD', 'NOOSE', 'NORTH', 'NOSEY', 'NOTCH', 'NOVEL', 'NUDGE', 'NURSE', 'NUTTY',
        'NYLON', 'NYMPH', 'OAKEN', 'OBESE', 'OCCUR', 'OCEAN', 'OCTET', 'ODDER', 'ODDLY', 'OFFAL',
        'OFFER', 'OFTEN', 'OLDEN', 'OLDER', 'OLIVE', 'OMBRE', 'OMEGA', 'ONION', 'ONSET', 'OPERA',
        'OPTIC', 'ORBIT', 'ORDER', 'ORGAN', 'OTHER', 'OTTER', 'OUGHT', 'OUNCE', 'OUTDO', 'OUTER',
        'OUTGO', 'OVARY', 'OVATE', 'OVERT', 'OWING', 'OWNER', 'OXIDE', 'OZONE', 'PADDY', 'PAGAN',
        'PAINT', 'PALER', 'PALSY', 'PAMPH', 'PANEL', 'PANIC', 'PANSY', 'PAPAL', 'PAPER', 'PARER',
        'PARIS', 'PARKA', 'PARRY', 'PARSE', 'PARTY', 'PASTA', 'PASTE', 'PASTY', 'PATCH', 'PATIO',
        'PATSY', 'PATTY', 'PAUSE', 'PAYEE', 'PEACE', 'PEACH', 'PEARL', 'PECAN', 'PEDAL', 'PENAL',
        'PENCE', 'PENNY', 'PERCH', 'PERIL', 'PERKY', 'PESKY', 'PESTO', 'PETAL', 'PETTY', 'PHASE',
        'PHONE', 'PHONY', 'PHOTO', 'PIANO', 'PICKY', 'PIECE', 'PIETY', 'PIGGY', 'PILOT', 'PINCH',
        'PINEY', 'PINKY', 'PINTO', 'PIPER', 'PIQUE', 'PITCH', 'PITHY', 'PIVOT', 'PIXEL', 'PIXIE',
        'PIZZA', 'PLACE', 'PLAID', 'PLAIN', 'PLAIT', 'PLANE', 'PLANK', 'PLANT', 'PLATE', 'PLAZA',
        'PLEAD', 'PLEAT', 'LEDGE', 'PLIER', 'PLOD', 'PLOP', 'PLUCK', 'PLUMB', 'PLUME', 'PLUMP',
        'PLUMS', 'PLUNK', 'PLUSH', 'POACH', 'POINT', 'POISE', 'POKER', 'POLAR', 'POLKA', 'POLYP',
        'POOCH', 'POPPY', 'PORCH', 'POSER', 'POSIT', 'POSSE', 'POUCH', 'POUND', 'POUTY', 'POWER',
        'PRANK', 'PRAWN', 'PREEN', 'PRESS', 'PRICE', 'PRICK', 'PRIDE', 'PRIED', 'PRIME', 'PRIMO',
        'PRINT', 'PRIOR', 'PRISM', 'PRIVY', 'PRIZE', 'PROBE', 'PRONE', 'PRONG', 'PROOF', 'PROSE',
        'PROUD', 'PROVE', 'PROWL', 'PROXY', 'PRUDE', 'PRUNE', 'PSALM', 'PUBIC', 'PUDGY', 'PULSE',
        'PUNCH', 'PUPIL', 'PUPPY', 'PUREE', 'PURER', 'PURGE', 'PURSE', 'PUSHY', 'PUTTY', 'PYGMY',
        'QUACK', 'QUAFF', 'QUAIL', 'QUAKE', 'QUALM', 'QUARK', 'QUART', 'QUASI', 'QUEEN', 'QUEER',
        'QUELL', 'QUERY', 'QUEST', 'QUEUE', 'QUICK', 'QUIET', 'QUILL', 'QUILT', 'QUIRK', 'QUITE',
        'QUOTA', 'QUOTE', 'RABBI', 'RABID', 'RACER', 'RADAR', 'RADII', 'RADIO', 'RADON', 'RAFT',
        'RAINY', 'RAISE', 'RAJAH', 'RALLY', 'RALPH', 'RAMEN', 'RANCH', 'RANDY', 'RANGE', 'RAPID',
        'RARER', 'RASPY', 'RATIO', 'RATTY', 'RAVEN', 'RAYON', 'RAZOR', 'REACH', 'REACT', 'READY',
        'REALM', 'REARM', 'REBAR', 'REBEL', 'REBUS', 'REBUT', 'RECAP', 'RECUR', 'RECUT', 'REEDY',
        'REFER', 'REGAL', 'REHAB', 'REIGN', 'RELAX', 'RELAY', 'RELIC', 'REMIT', 'RENAL', 'RENEW',
        'REPAY', 'REPEL', 'REPLY', 'RERUN', 'RESET', 'RESIN', 'RETCH', 'RETRO', 'RETRY', 'REUSE',
        'REVEL', 'REVUE', 'RHINO', 'RHYME', 'RIDER', 'RIDGE', 'RIFLE', 'RIGHT', 'RIGID', 'RIGOR',
        'RINSE', 'RIPEN', 'RIPER', 'RISEN', 'RISER', 'RISKY', 'RIVAL', 'RIVER', 'RIVET', 'ROACH',
        'ROAST', 'ROBIN', 'ROBOT', 'ROCKY', 'RODEO', 'ROGER', 'ROGUE', 'ROOMY', 'ROOST', 'ROTOR',
        'ROUGE', 'ROUGH', 'ROUND', 'ROUSE', 'ROUTE', 'ROVER', 'ROWDY', 'ROWER', 'ROYAL', 'RUDDY',
        'RUDER', 'RUGBY', 'RUING', 'RULER', 'RUMBA', 'RUMOR', 'RUPEE', 'RURAL', 'RUSTY', 'SADLY',
        'SAFER', 'SAINT', 'SALAD', 'SALLY', 'SALON', 'SALSA', 'SALTY', 'SALVE', 'SALVO', 'SANDY',
        'SANER', 'SAPID', 'SASSY', 'SATIN', 'SATYR', 'SAUCE', 'SAUCY', 'SAUNA', 'SAUTE', 'SAVOR',
        'SAVOY', 'SAVVY', 'SCALD', 'SCALE', 'SCALP', 'SCALY', 'SCAMP', 'SCANT', 'SCARE', 'SCARF',
        'SCARY', 'SCENE', 'SCENT', 'SCION', 'SCOFF', 'SCOLD', 'SCONE', 'SCOOP', 'SCOPE', 'SCORE',
        'SCORN', 'SCOUT', 'SCOWL', 'SCRAM', 'SCRAP', 'SCREE', 'SCREW', 'SCRUB', 'SEAMY', 'SEDAN',
        'SEEDY', 'SEGUE', 'SEIZE', 'SEMEN', 'SENSE', 'SEPIA', 'SERIF', 'SERUM', 'SERVE', 'SETUP',
        'SEVEN', 'SEVER', 'SEWER', 'SHACK', 'SHADE', 'SHADY', 'SHAFT', 'SHAKE', 'SHAKY', 'SHALE',
        'SHALL', 'SHALT', 'SHAME', 'SHANK', 'SHAPE', 'SHARD', 'SHARE', 'SHARK', 'SHARP', 'SHAVE',
        'SHAWL', 'SHEAR', 'SHEEN', 'SHEEP', 'SHEER', 'SHEET', 'SHEIK', 'SHELF', 'SHELL', 'SHIFT',
        'SHINE', 'SHINY', 'SHIRE', 'SHIRK', 'SHIRT', 'SHOAL', 'SHOCK', 'SHONE', 'SHOOK', 'SHOOT',
        'SHORE', 'SHORN', 'SHORT', 'SHOUT', 'SHOVE', 'SHOWN', 'SHOWY', 'SHREW', 'SHRUB', 'SHRUG',
        'SHUCK', 'SHUNT', 'SHUSH', 'SHYLY', 'SIEGE', 'SIEVE', 'SIGHT', 'SIGMA', 'SILKY', 'SILLY',
        'SINCE', 'SINEW', 'SINGE', 'SIREN', 'SISSY', 'SIXTH', 'SIXTY', 'SKATE', 'SKIER', 'SKIFF',
        'SKILL', 'SKIMP', 'SKIRT', 'SKULK', 'SKULL', 'SKUNK', 'SLACK', 'SLAIN', 'SLANG', 'SLANT',
        'SLASH', 'SLATE', 'SLAVE', 'SLEEK', 'SLEEP', 'SLEET', 'SLEPT', 'SLICE', 'SLICK', 'SLIDE',
        'SLIME', 'SLIMY', 'SLING', 'SLINK', 'SLOPE', 'SLOSH', 'SLOTH', 'SLUMP', 'SLUNG', 'SLUNK',
        'SLURP', 'SLUSH', 'SLYLY', 'SMACK', 'SMALL', 'SMART', 'SMASH', 'SMEAR', 'SMELL', 'SMELT',
        'SMILE', 'SMIRK', 'SMITE', 'SMITH', 'SMOCK', 'SMOKE', 'SMOKY', 'SNACK', 'SNAFU', 'SNAIL',
        'SNAKE', 'SNAKY', 'SNARE', 'SNARL', 'SNEAK', 'SNEER', 'SNIDE', 'SNIFF', 'SNIPE', 'SNOOP',
        'SNORE', 'SNORT', 'SNOUT', 'SNOWY', 'SNUCK', 'SNUFF', 'SOAPY', 'SOBER', 'SOGGY', 'SOLAR',
        'SOLID', 'SOLVE', 'SONAR', 'SONIC', 'SOOTH', 'SOOTY', 'SORRY', 'SOUND', 'SOUTH', 'SPACE',
        'SPADE', 'SPANK', 'SPARE', 'SPARK', 'SPASM', 'SPAWN', 'SPEAK', 'SPEAR', 'SPECK', 'SPEED',
        'SPELL', 'SPEND', 'SPENT', 'SPICE', 'SPICY', 'SPIDER', 'SPIED', 'SPIEL', 'SPIKE', 'SPIKY',
        'SPILL', 'SPINE', 'SPINY', 'SPIRE', 'SPITE', 'SPLAT', 'SPLIT', 'SPOIL', 'SPOKE', 'SPOOF',
        'SPOOK', 'SPOOL', 'SPOON', 'SPORE', 'SPORT', 'SPOUT', 'SPRAY', 'SPREE', 'SPRIG', 'SPUNK',
        'SPURN', 'SPURT', 'SQUAD', 'SQUAT', 'SQUIB', 'STACK', 'STAFF', 'STAGE', 'STAID', 'STAIN',
        'STAIR', 'STAKE', 'STALE', 'STALK', 'STALL', 'STAMP', 'STAND', 'STANK', 'STAPH', 'STARE',
        'STARK', 'START', 'STASH', 'STATE', 'STAVE', 'STEAD', 'STEAK', 'STEAL', 'STEAM', 'STEEL',
        'STEEP', 'STEER', 'STEIN', 'STERN', 'STICK', 'STIFF', 'STILL', 'STILT', 'STING', 'STINK',
        'STINT', 'STOCK', 'STOIC', 'STOKE', 'STOLE', 'STOMP', 'STONE', 'STONY', 'STOOD', 'STOOL',
        'STOOP', 'STORE', 'STORK', 'STORM', 'STORY', 'STOUT', 'STOVE', 'STRAP', 'STRAW', 'STRAY',
        'STRIP', 'STRUT', 'STUCK', 'STUDY', 'STUFF', 'STUMP', 'STUNG', 'STUNK', 'STUNT', 'STYLE',
        'SUAVE', 'SUGAR', 'SUING', 'SUITE', 'SULKY', 'SUNNY', 'SUPER', 'SURER', 'SURGE', 'SURLY',
        'SUSHI', 'SWAMP', 'SWANK', 'SWARM', 'SWASH', 'SWATH', 'SWEAR', 'SWEAT', 'SWEEP', 'SWEET',
        'SWELL', 'SWEPT', 'SWIFT', 'SWILL', 'SWINE', 'SWING', 'SWIPE', 'SWIRL', 'SWISH', 'SWOON',
        'SWOOP', 'SWORD', 'SWORE', 'SWORN', 'SWUNG', 'SYNOD', 'SYRUP', 'TABBY', 'TABLE', 'TABOO',
        'TACIT', 'TACKY', 'TAFFY', 'TAINT', 'TAKEN', 'TAKER', 'TALLY', 'TALON', 'TAMER', 'TANGO',
        'TANGY', 'TAPER', 'TAPIR', 'TARDY', 'TAROT', 'TASTE', 'TASTY', 'TATTY', 'TAUNT', 'TAWNY',
        'TEACH', 'TEARY', 'TEASE', 'TEDDY', 'TEETH', 'TEMPO', 'TENET', 'TENOR', 'TENSE', 'TENTH',
        'TEPEE', 'TEPID', 'TERRA', 'TERSE', 'TESTY', 'THANK', 'THEFT', 'THEIR', 'THEME', 'THERE',
        'THESE', 'THICK', 'THIEF', 'THIGH', 'THING', 'THINK', 'THIRD', 'THONG', 'THORN', 'THOSE',
        'THREE', 'THREW', 'THROB', 'THROW', 'THRUM', 'THUMB', 'THUMP', 'TIARA', 'TIBIA', 'TIDAL',
        'TIGER', 'TIGHT', 'TILDE', 'TIMER', 'TIMID', 'TIPSY', 'TITAN', 'TITLE', 'TOAST', 'TODAY',
        'TODDY', 'TOKEN', 'TONAL', 'TONER', 'TONGS', 'TONIC', 'TOOTH', 'TOPAZ', 'TOPIC', 'TORCH',
        'TORSO', 'TOTAL', 'TOTEM', 'TOUCH', 'TOUGH', 'TOWEL', 'TOWER', 'TOXIC', 'TRACE', 'TRACK',
        'TRACT', 'TRADE', 'TRAIL', 'TRAIN', 'TRAIT', 'TRAMP', 'TRASH', 'TRAWL', 'TREAD', 'TREAT',
        'TREND', 'TRIAL', 'TRIBE', 'TRICE', 'TRICK', 'TRIED', 'TRIPE', 'TRITE', 'TROLL', 'TROOP',
        'TROPE', 'TROUT', 'TRUCE', 'TRUCK', 'TRUER', 'TRULY', 'TRUMP', 'TRUNK', 'TRUSS', 'TRUST',
        'TRUTH', 'TRYST', 'TUBAL', 'TUBER', 'TULIP', 'TULLE', 'TUMOR', 'TUNER', 'TUNIC', 'TURBO',
        'TUTOR', 'TWAIN', 'TWANG', 'TWEAK', 'TWEED', 'TWEET', 'TWICE', 'TWILL', 'TWINE', 'TWIRL',
        'TWIST', 'TWIXT', 'TYING', 'UDDER', 'ULCER', 'ULTRA', 'UMBRA', 'UNCLE', 'UNCUT', 'UNDER',
        'UNDID', 'UNDUE', 'UNFED', 'UNFIT', 'UNHIP', 'UNIFY', 'UNION', 'UNITE', 'UNITY', 'UNLIT',
        'UNMET', 'UNRIG', 'UNSET', 'UNTIE', 'UNTIL', 'UNWED', 'UNZIP', 'UPPER', 'UPSET', 'URBAN',
        'URINE', 'USAGE', 'USHER', 'USING', 'USUAL', 'USURP', 'UTILE', 'UTTER', 'VAGUE', 'VALET',
        'VALID', 'VALOR', 'VALUE', 'VALVE', 'VAPID', 'VAPOR', 'VAULT', 'VAUNT', 'VEGAN', 'VENOM',
        'VENUE', 'VERGE', 'VERSE', 'VERVE', 'VICAR', 'VIDEO', 'VIGOR', 'VILLA', 'VINYL', 'VIOLA',
        'VIPER', 'VIRAL', 'VIRUS', 'VISIT', 'VISOR', 'VISTA', 'VITAL', 'VIVID', 'VIXEN', 'VOCAL',
        'VODKA', 'VOGUE', 'VOICE', 'VOILA', 'VOMIT', 'VOTER', 'VOUCH', 'VOWEL', 'VYING', 'WACKY',
        'WAFER', 'WAGER', 'WAGON', 'WAIST', 'WAIVE', 'WALTZ', 'WARTY', 'WASTE', 'WATCH', 'WATER',
        'WAVER', 'WAXEN', 'WEARY', 'WEAVE', 'WEDGE', 'WEEDY', 'WEIGH', 'WEIRD', 'WELCH', 'WELSH',
        'WENCH', 'WHACK', 'WHALE', 'WHARF', 'WHEAT', 'WHEEL', 'WHELP', 'WHERE', 'WHICH', 'WHIFF',
        'WHILE', 'WHINE', 'WHINY', 'WHIRL', 'WHISK', 'WHITE', 'WHOLE', 'WHOOP', 'WHOSE', 'WIDEN',
        'WIDER', 'WIDOW', 'WIDTH', 'WIELD', 'WIGHT', 'WILLY', 'WIMPY', 'WINCE', 'WINCH', 'WINDY',
        'WIPER', 'WIRER', 'WISER', 'WITCH', 'WITTY', 'WOKEN', 'WOMAN', 'WOMEN', 'WOODY', 'WOOER',
        'WOOLY', 'WOOZY', 'WORDY', 'WORLD', 'WORRY', 'WORSE', 'WORST', 'WORTH', 'WOULD', 'WOUND',
        'WOVEN', 'WRACK', 'WRATH', 'WREAK', 'WRECK', 'WREST', 'WRING', 'WRIST', 'WRITE', 'WRONG',
        'WROTE', 'WRUNG', 'WRYLY', 'YACHT', 'YEARN', 'YEAST', 'YIELD', 'YOUNG', 'YOUTH', 'ZEBRA',
        'ZESTY', 'ZONAL',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->info('Clearing existing words...');
            Word::truncate();
        }

        $file = $this->option('file');

        if ($file && file_exists($file)) {
            return $this->importFromFile($file);
        }

        return $this->importDefaultWords();
    }

    /**
     * Import words from a file.
     */
    private function importFromFile(string $filePath): int
    {
        $this->info("Importing words from: {$filePath}");

        $content = file_get_contents($filePath);
        $words = array_filter(array_map('trim', explode("\n", $content)));
        $words = array_filter($words, fn($w) => strlen($w) === 5 && ctype_alpha($w));

        return $this->insertWords(array_map('strtoupper', $words));
    }

    /**
     * Import default word list.
     */
    private function importDefaultWords(): int
    {
        $this->info('Importing default Wordle word list...');

        // Filter valid 5-letter words
        $solutionWords = array_filter($this->solutionWords, fn($w) => strlen($w) === 5);

        return $this->insertWords($solutionWords, true);
    }

    /**
     * Insert words into the database.
     */
    private function insertWords(array $words, bool $areSolutions = false): int
    {
        $bar = $this->output->createProgressBar(count($words));
        $bar->start();

        $inserted = 0;
        $chunks = array_chunk($words, 100);

        DB::transaction(function () use ($chunks, $areSolutions, &$inserted, $bar) {
            foreach ($chunks as $chunk) {
                $data = array_map(function ($word) use ($areSolutions) {
                    return [
                        'word' => strtoupper($word),
                        'is_solution' => $areSolutions,
                        'is_valid' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $chunk);

                // Use upsert to handle duplicates
                Word::upsert($data, ['word'], ['is_solution', 'is_valid', 'updated_at']);
                
                $inserted += count($chunk);
                $bar->advance(count($chunk));
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully imported {$inserted} words.");

        return Command::SUCCESS;
    }
}
