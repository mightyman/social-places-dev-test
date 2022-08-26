<template>
    <v-container>
        <v-row>
            <v-col cols="8">
                <v-card>
                    <v-card-title>
                        Welcome prospective developer...
                    </v-card-title>
                    <v-card-text>
                        <p>
                            Congratulations on getting set up and logged in.<br/><br/>

                            There are three major and one minor aspects outlined below of the test for which you will be evaluated:
                        </p>
                        <ol>
                            <li>
                                Implement the import functionality in the StoreController
                                <ol type="a">
                                    <li>Validate the data using the built in validator</li>
                                    <li><b>Outcomes: </b>To see if you can adapt to the environment, your knowledge (also known as Googling skills) of Symfony, PHP and code reading\following skills.</li>
                                </ol>
                            </li>
                            <li>
                                Improve performance of the above import and relating store table without directly editing the database.
                                <ol type="a">
                                    <li><b>Outcomes: </b>Ability to optimize queries and approach for queries given the circumstance and improve database performance where possible.</li>
                                </ol>
                            </li>
                            <li>
                                Design and create a Contact entity\model, form and controller.
                                <ol type="a">
                                    <li>Must be within the existing framework of Symfony and Vuetify</li>
                                    <li>
                                        <b>Brief: </b>A store may have multiple points of contact, upon creating the contact:
                                        <ul>
                                            <li>The user should associate the contact to a store.</li>
                                            <li>Contain "important" information of said contact.</li>
                                            <li>The contact form should be accessible without needing to log in.</li>
                                            <li>After creation a mail should be sent to all admins informing them of the new contact added.</li>
                                        </ul>
                                    <li><b>Outcomes: </b>Tests knowledge of the Request\Response work-flow of Symfony. Additionally Vue (using Vuetify framework), observation skills, and understanding.</li>
                                </ol>
                            </li>
                            <li>
                                Notice security vulnerabilities, poor variable names or schemes followed, "poro speling" and bugs
                                <ol type="a">
                                    <li><b>For example: </b>There is a bug when exporting stores, that the export does not respect the filters, please find and fix the issue.</li>
                                </ol>
                            </li>
                        </ol>
                        <br/>
                        <p>
                            Feel free to "spice" it up. Bonus points for creativity and "wowing the judges".
                        </p>
                        <p>
                        While we do not "mark" you on time taken, please let us know roughly how long it took to complete the test and how else we can improve.
                        </p>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    name: "DashboardIndex",
}
</script>

<style scoped>

</style>
