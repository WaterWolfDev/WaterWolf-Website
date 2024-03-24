<template>
    <!-- Comments Form -->
    <div class="ts-comments-box card mb-3">
        <h4 class="card-header">Comments</h4>
        <div class="card-body">
            <div class="list-group mb-3" id="comments-list" v-if="posts.length > 0">
                <div
                    v-for="row in posts" :key="row.id"
                    class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">
                            {{ row.username }}
                        </h5>
                        <small class="text-muted">
                            {{ timeAgo(row.tstamp) }}
                        </small>
                    </div>
                    <p class="mb-1">
                        {{ row.comment }}
                    </p>
                    <div class="buttons" v-if="isMod">
                        <button type="button" class="btn btn-sm btn-danger"
                                @click.prevent="deleteComment(row.id)">
                            <i class="bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>

            <template v-if="isLoggedIn">
                <h4>Make a Comment</h4>

                <form method="post" action="" @submit.prevent="postComment">
                    <div class="form-group">
                        <label class="form-label" for="form-comment">Message:</label>
                        <textarea class="form-control" name="comment" id="form-comment" rows="3"
                                  v-model="newComment" required></textarea>
                    </div>

                    <div class="buttons mt-2">
                        <button type="submit" class="btn btn-primary">Comment!</button>
                    </div>
                </form>
            </template>
            <template v-else>
                <h4>Join WaterWolf to Comment</h4>
                <p class="card-text">
                    Want to comment? <a href="/register">Make an account</a> or <a href="/login">login</a>.
                </p>
            </template>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, shallowRef} from "vue";
import {useAxios} from "~/vue/vendor/axios.js";
import {useNotify} from "~/vue/functions/useNotify.js";
import {useSweetAlert} from "~/vue/vendor/sweetalert.js";
import {DateTime} from "luxon";

const props = defineProps({
    location: {
        type: String,
        required: true
    },
    isLoggedIn: {
        type: Boolean,
        default: false
    },
    isMod: {
        type: Boolean,
        default: false
    }
});

const apiUrl = `/api/comments/${props.location}`;

const axios = useAxios();

const posts = shallowRef([]);
const loadPosts = () => {
    axios.get(apiUrl).then((r) => {
        posts.value = r.data;
    });
};

const timeAgo = (timestamp) => {
    return DateTime.fromSeconds(timestamp).toLocaleString(DateTime.DATETIME_MED);
}

const {notifySuccess} = useNotify();

const newComment = ref('');
const postComment = () => {
    axios.post(apiUrl, {
        comment: newComment.value,
    }).then((r) => {
        if (r.data.success) {
            notifySuccess('Comment posted!');
            loadPosts();
        }
    }).finally(() => {
        newComment.value = '';
    });
};

const {confirmDelete} = useSweetAlert();

const deleteComment = (id) => {
    confirmDelete({
        title: 'Delete comment?'
    }).then((r) => {
        if (r.value) {
            axios.delete(apiUrl, {
                params: {
                    id: id
                }
            }).then((r) => {
                if (r.data.success) {
                    notifySuccess('Comment removed.');
                    loadPosts();
                }
            })
        }
    });
};

onMounted(() => {
    loadPosts();
});
</script>
