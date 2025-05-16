<?php
require_once "./vendor/autoload.php";

use LearnositySdk\Request\Init;
use LearnositySdk\Utils\Uuid;

$consumer_key = "yis0TYCu7U9V4o7M";
$consumer_secret = "74c5fd430cf1242a527f6223aebd42d30464be22";

$session_id = Uuid::generate();
$security = [
    'consumer_key' => $consumer_key,
    'domain'       => "localhost"
];

$request = <<<REQ
{
    "activity_id": "itemsassessdemo",
    "name": "Items API demo - assess activity",
    "rendering_type": "assess",
    "type": "local_practice",
    "session_id": "$session_id",
    "user_id": "ANONYMIZED_USER_ID",
    "config": {
        "navigation": {
            "show_intro": false,
            "resource_items": ["fares_hackday_test"],
            "scroll_to_test": false,
            "scroll_to_top": false
        },
        "regions": "items-only",
        "region_overrides": {
            "right.resource_button": true
        },
        "questions_api_init_options": {
            "fontsize": "xlarge",
            "attribute_overrides" : {
                "instant_feedback": true
            },
            "showCorrectAnswers": true,
            "show_distractor_rationale": {
                "per_question": "always",
                "per_response": "always"
            },
            "labelBundle": {
                "checkAnswer": "Submit Your Answer!"
            }
        }
    }
}
REQ;

if(is_string($request)){
	$request = json_decode($request, true);
}
$randomJson = file_get_contents('./random.json');
$randomObject = json_decode($randomJson, true);
$request['random'] = $randomObject;


$Init = new Init('items', $security, $consumer_secret, $request);
$signedRequest = $Init->generate();

$version = "latest-lts";
?>

    <!DOCTYPE html>
    <html>
        <head>

            <title>Items API Test</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@200..800&display=swap" rel="stylesheet">
            <style>
                <?=file_get_contents('./style.css');?>
            </style>
        </head>
        <body>


            <header>
                <div class="text">
                    <h1 class="jeopardy">
                    JEOPARDY!
                    </h1>
                    <p>Learnosity edition</p>
                    <p class="tagline">Quiz yourself on what you learned this year before the final exam!</p>
                </div>
               
                <p>Your score: <span class="score">0<span></p>
            </header>
            <main>
                <div class="board hidden"></div>
                <div class="current-item-card-wrapper">
                    <p class="current-item-info"></p>
                    <div id=learnosity_assess></div>
                    <button class="continue-game">Continue Game</button>
                </div>
            </main>
    




            <script src="https://items.learnosity.com?<?=$version?>"></script>

            <script type="module">
                // grab the random activity json used in the request on the server-side, to know the categories needed
                // in order to build the jeopardy board
                const data = JSON.parse(`<?=$randomJson?>`)

                const labels = data.groups.map(group => group.label);

                function chunkArray(arr, size) {
                    if (size <= 0) throw new Error('Chunk size must be a positive integer');
                    return arr.reduce((acc, _, i) => {
                    if (i % size === 0) acc.push(arr.slice(i, i + size));
                    return acc;
                    }, []);
                }
                
                const callbacks = {
                    readyListener: function () {
                        console.log("Items API has successfully initialized.");

                        /** construct the JSON object needed for rendering the board, consisting of the items in each 
                         * random group, and their indecies for navigation, and their points value in the dgame
                         */
                        const items = Object.keys(itemsApp.getItems());
                        const chunks = chunkArray(items, 5);
                        let boardJson = {}; 
                        labels.forEach((label, index) => {
                            const chunk = chunks[index]
                            boardJson[label] = {
                                items: chunk.map((reference, innerIndex) => {
                                    return {
                                        reference: reference,
                                        navIndex: items.indexOf(reference),
                                        points: (innerIndex+1)*100
                                    }
                                })
                            }
                        })

                        /**
                         * render the board and handle navigating to the needed item and animating (transtioning) the board out
                         * and assessment player item in
                         */
                        const currentItemCard = document.querySelector('.current-item-card-wrapper')
                        const currentItemInfo = currentItemCard.querySelector('.current-item-info');
                        const assessmentPlayer = currentItemCard.querySelector('.lrn-assess')
                        const continueGameBtn = currentItemCard.querySelector('button.continue-game')
                        const score = document.querySelector('.score')
                        continueGameBtn.addEventListener('click', (event) => {
                            const button = event.target.closest('button')
                            currentItemInfo.innerText = "";
                            board.classList.toggle('hidden');
                            button.classList.toggle('active');
                            assessmentPlayer.classList.toggle('active')

                        })
                        const board = document.querySelector('.board')

                        for(let label in boardJson) {
                            const items = boardJson[label].items
                            const column = document.createElement('div');
                            const p = document.createElement('p')
                            p.innerText = label;
                            p.classList.add('category-label')
                            column.classList.add('column')
                            column.setAttribute('data-category', label);
                            column.appendChild(p);
                            board.appendChild(column);
                            items.forEach(item => {
                                const square = document.createElement('button');
                                square.classList.add('square');
                                square.setAttribute('data-nav-index', item.navIndex)
                                square.setAttribute('data-points', item.points)
                                square.setAttribute('data-reference', item.reference)
                                square.innerHTML = `<span class="points">${item.points}</span>`;
                                column.appendChild(square)
                                
                                /** fade the board in by removing hidden */
                                board.classList.toggle('hidden')

                                /** click event to go to underlying item */
                                square.addEventListener('click', (event) => {
                                    const button = event.target.closest('button')
                                    const navIndex = parseInt(button.getAttribute('data-nav-index'));
                                    const itemReference = button.getAttribute('data-reference');
                                    const points = button.getAttribute('data-points');
                                    const category = button.parentElement.getAttribute('data-category');
                                    // TODO: This is an annoying bug, why is itemRef only logged once?
                                    // event bubbling, done put other els inside button el.
                                    console.log("itemReference >>", itemReference)
                                    /** handle the click */
                                    itemsApp.items().goto(navIndex);
                                    board.classList.toggle('hidden');
                                    assessmentPlayer.classList.toggle('active')
                                    currentItemInfo.innerText = `${category} for ${points} points.`
                                    if(itemsApp.getItems()[itemReference].attempt_status === "fully_attempted") {
                                        console.log("ITEM ALEADY ANSWERED!")
                                        continueGameBtn.classList.toggle('active')
                                    } 
                                    // mark square as played
                                    if(!square.classList.contains('answered')) {
                                        square.classList.add('answered');
                                    }
                                })
                            })
                        }



                        /**
                         * handle fading board back in , assessment player back out , updating the score, and
                         * marking the square as correct / incorrect
                         * when check answer button is pressed
                         */
                        let incorrectAudioFeature;
                        let correctAudioFeature;
                        Object.keys(itemsApp.questions()).forEach(response_id => {
                            const question = itemsApp.question(response_id);
                            // once the question is changed grab the audio player feature instances for the correct / incorrect answer
                            // these will be be visually hidden inside the resouce item but their audio is needed 
                            // to play the incorrect audio or correct jeopardy sound when the questions as validated.
                            question.once('changed', () => {
                                const audioFeatures = Object.values(itemsApp.getFeatures()).filter(feature => feature.type === "audioplayer")
                                incorrectAudioFeature = itemsApp.feature(audioFeatures.find(feature => feature.metadata.hint === "incorrect").feature_id)
                                correctAudioFeature = itemsApp.feature(audioFeatures.find(feature => feature.metadata.hint === "correct").feature_id)
                            })
                            const quesitonAlreadyAnswered = !question.isEnabled();
                            if(!quesitonAlreadyAnswered) {
                                question.on('validated', () => {
                                    const currentItemReference = itemsApp.getCurrentItem().reference
                                    const square = board.querySelector(`button[data-reference="${currentItemReference}"]`)
                                    const points = parseInt(square.getAttribute("data-points"))
                                    console.log('validated')
                                    question.disable();
                                    continueGameBtn.classList.toggle('active')
                                    if(question.isValid()) {
                                        correctAudioFeature.audio.play();
                                        square.classList.add('correct')
                                        score.innerText = parseInt(score.innerText) +  points
                                    } else {
                                        incorrectAudioFeature.audio.play();
                                        square.classList.add('incorrect')
                                    }
                                })
                            } 
                        })

                    },
                    errorListener: function (err) {
                        console.log(err);
                    }
                };

                window.itemsApp = LearnosityItems.init(<?=$signedRequest?>, callbacks);
            </script>
        </body>
    </html>

